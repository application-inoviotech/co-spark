<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Policy;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\MemberAlert;
use App\Services\Payments\PaymentProcessor;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Insurance premium and Vacation Token payments made from the member dashboard.
 * SOW: premiums are separate from dues; eligible premium earns IT (Associate 5%, Premium 7%);
 * VT is purchased at $1 face value and tracked in lots.
 */
class MemberBillingService
{
    public function __construct(
        private PaymentProcessor $payments,
        private MembershipService $memberships,
        private TokenLedger $ledger,
    ) {}

    public function payPremium(User $user, Policy $policy, PaymentMethod $method): Payment
    {
        if ($policy->amount_due <= 0) {
            throw ValidationException::withMessages(['policy' => 'This policy has no balance due.']);
        }

        $payment = $this->payments->charge(
            $user, Payment::TYPE_PREMIUM, (float) $policy->amount_due,
            "{$policy->type} · {$policy->policy_number} · annual", $method->toCard(), $method, $policy,
            ['period_start' => $policy->starts_on, 'period_end' => $policy->expires_on],
        );

        if ($payment->succeeded()) {
            DB::transaction(function () use ($user, $policy, $payment) {
                $policy->update(['amount_due' => 0, 'due_on' => null]);
                $this->creditInsuranceTokens($user, $policy, $payment);
            });
        }

        return $payment;
    }

    /** IT for the policy year, credited once, at the member's plan rate. */
    public function insuranceTokensFor(User $user, Policy $policy): int
    {
        $rate = $user->membership?->status === 'active' ? $user->membership->planConfig()['insurance_token_rate'] : 0;

        return (int) round($policy->annual_premium * $rate);
    }

    public function buyVacationTokens(User $user, int $quantity, PaymentMethod $method): Payment
    {
        $payment = $this->payments->charge($user, Payment::TYPE_VACATION_TOKENS, $quantity, "{$quantity} VT purchase", $method->toCard(), $method);

        if ($payment->succeeded()) {
            DB::transaction(function () use ($user, $quantity, $payment) {
                $this->ledger->credit($user, TokenTransaction::VACATION, $quantity, 'purchased', 'Vacation Token purchase', $payment, $payment->reference);
                $user->vacationTokenLots()->create([
                    'payment_id' => $payment->id,
                    'lot_number' => sprintf('VT-LOT-%02d', $user->vacationTokenLots()->count() + 1),
                    'purchased_on' => today(),
                    'face_value' => $quantity,
                ]);
            });
        }

        return $payment;
    }

    /** Retry a failed payment of any kind with the chosen method. */
    public function retry(User $user, Payment $failed, PaymentMethod $method): Payment
    {
        return match ($failed->type) {
            Payment::TYPE_DUES => $this->memberships->payDues($user->membership, $user->membership->billing_frequency, $method),
            Payment::TYPE_PREMIUM => $this->payPremium($user, $failed->payable instanceof Policy ? $failed->payable : throw ValidationException::withMessages(['payment' => 'This policy is no longer available.']), $method),
            Payment::TYPE_VACATION_TOKENS => $this->buyVacationTokens($user, (int) $failed->amount, $method),
            default => throw ValidationException::withMessages(['payment' => 'This payment cannot be retried online.']),
        };
    }

    /**
     * Admin records a premium paid outside the portal (e.g. directly to the carrier). SOW: once an eligible
     * premium payment/renewal is recorded, the policy is updated and IT are credited for that policy year.
     */
    public function recordPremium(Policy $policy, float $amount, string $paidOn, ?string $reference): Payment
    {
        return DB::transaction(function () use ($policy, $amount, $paidOn, $reference) {
            $user = $policy->user;
            $payment = $user->payments()->create([
                'payable_type' => $policy->getMorphClass(), 'payable_id' => $policy->id,
                'type' => Payment::TYPE_PREMIUM, 'amount' => $amount, 'status' => 'succeeded',
                'description' => "{$policy->type} · {$policy->policy_number} · annual (recorded by COSPARK)",
                'method_label' => 'Paid to carrier', 'gateway' => 'manual', 'gateway_reference' => $reference,
                'period_start' => $policy->starts_on, 'period_end' => $policy->expires_on, 'paid_at' => $paidOn,
            ]);
            $due = max(0, round((float) $policy->amount_due - $amount, 2));
            $policy->update(['amount_due' => $due, 'due_on' => $due > 0 ? $policy->due_on : null]);
            $this->creditInsuranceTokens($user, $policy, $payment);

            return $payment;
        });
    }

    private function creditInsuranceTokens(User $user, Policy $policy, Payment $payment): void
    {
        $tokens = $this->insuranceTokensFor($user, $policy);
        if ($tokens <= 0 || $this->ledger->hasInsuranceTokens($policy)) {
            return;
        }

        $membership = $user->membership;
        $rate = $membership->planConfig()['insurance_token_rate'] * 100;

        $this->ledger->credit($user, TokenTransaction::INSURANCE, $tokens, 'earned',
            "{$policy->type} policy · PY {$policy->policyYear()} · {$rate}% of $".number_format($policy->annual_premium),
            $payment, $policy->policy_number, ['policy_id' => $policy->id, 'policy_year' => $policy->policyYear()]);

        $user->notify(new MemberAlert('Tokens', "+{$tokens} Insurance Tokens credited", "{$policy->type} policy premium received.", 'wallet'));
    }
}
