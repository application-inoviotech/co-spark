<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Policy;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\VacationRequest;
use Illuminate\Support\Facades\DB;

/** Writes and reads the DT / IT / VT token ledger. Balances are the sum of entries per type. */
class TokenLedger
{
    public function credit(User $user, string $type, float $amount, string $kind, string $description, ?Payment $payment = null, ?string $reference = null, array $extra = []): TokenTransaction
    {
        return $user->tokenTransactions()->create([
            'token_type' => $type,
            'kind' => $kind,
            'amount' => abs($amount),
            'description' => $description,
            'reference' => $reference,
            'payment_id' => $payment?->id,
            ...$extra,
        ]);
    }

    public function debit(User $user, string $type, float $amount, string $kind, string $description, ?string $reference = null, string $status = 'posted'): TokenTransaction
    {
        return $user->tokenTransactions()->create([
            'token_type' => $type,
            'kind' => $kind,
            'amount' => -abs($amount),
            'description' => $description,
            'reference' => $reference,
            'status' => $status,
        ]);
    }

    /** @return array{DT: float, IT: float, VT: float} */
    public function balances(User $user): array
    {
        $sums = $user->tokenTransactions()
            ->selectRaw('token_type, SUM(amount) as total')
            ->groupBy('token_type')
            ->pluck('total', 'token_type');

        return [
            TokenTransaction::DUES => (float) ($sums[TokenTransaction::DUES] ?? 0),
            TokenTransaction::INSURANCE => (float) ($sums[TokenTransaction::INSURANCE] ?? 0),
            TokenTransaction::VACATION => (float) ($sums[TokenTransaction::VACATION] ?? 0),
        ];
    }

    public function balance(User $user, string $type): float
    {
        return $this->balances($user)[$type];
    }

    /**
     * Tokens the member can still spend. DT applied to pending vacation requests are only deducted on
     * confirmation, so they are held back here; pass the vacation being confirmed to release its own hold.
     */
    public function spendable(User $user, string $type, ?VacationRequest $confirming = null): float
    {
        $held = $type === TokenTransaction::DUES
            ? (int) $user->vacationRequests()->where('status', 'pending')->when($confirming, fn ($q) => $q->whereKeyNot($confirming->id))->sum('tokens_applied')
            : 0;

        return max(0, $this->balance($user, $type) - $held);
    }

    /** IT already credited for this policy year (one allocation per policy per year). */
    public function hasInsuranceTokens(Policy $policy): bool
    {
        return TokenTransaction::where('policy_id', $policy->id)
            ->where('policy_year', $policy->policyYear())
            ->where('kind', 'earned')
            ->exists();
    }

    /**
     * SOW: a policy cancelled mid-year forfeits that year's IT; completed years remain.
     */
    public function forfeitCurrentYear(Policy $policy): void
    {
        DB::transaction(function () use ($policy) {
            Policy::whereKey($policy->id)->lockForUpdate()->firstOrFail();
            $earned = TokenTransaction::where('policy_id', $policy->id)
                ->where('policy_year', $policy->policyYear())
                ->where('kind', 'earned')
                ->where('status', 'posted')
                ->get();

            foreach ($earned as $entry) {
                $entry->update(['status' => 'forfeited']);
                $this->debit($policy->user, TokenTransaction::INSURANCE, $entry->amount, 'forfeited',
                    "{$policy->type} policy cancelled mid-year · current-year IT forfeited", $policy->policy_number);
            }
        });
    }
}
