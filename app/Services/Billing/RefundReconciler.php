<?php

namespace App\Services\Billing;

use App\Models\MemberQrCode;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\User;
use App\Models\VacationTokenLot;
use App\Services\MembershipService;
use App\Services\TokenLedger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Append-only, one-time reversal after a full gateway refund/void has been confirmed. */
class RefundReconciler
{
    public function __construct(private AuthorizeNet $gateway, private TokenLedger $ledger, private MembershipService $memberships) {}

    public function refund(Payment $payment): array
    {
        return $this->withLockedPayment($payment, function (Payment $p) {
            if ($p->status !== 'refunded') {
                $environment = config('services.authorizenet.sandbox') ? 'sandbox' : 'production';
                if ($p->status !== 'succeeded' || $p->gateway !== 'authorizenet' || ! $p->gateway_reference || $p->gateway_environment !== $environment) {
                    throw ValidationException::withMessages(['payment' => 'Verify the successful payment and gateway environment before refunding.']);
                }
                $this->gateway->refund($p->gateway_reference);
            }

            return $this->reconcileLocked($p);
        });
    }

    /** Only call after the gateway confirmed a full refund, or for an already-refunded stored payment. */
    public function confirmed(Payment $payment): array
    {
        return $this->withLockedPayment($payment, fn (Payment $p) => $this->reconcileLocked($p));
    }

    private function withLockedPayment(Payment $payment, callable $action): array
    {
        return DB::transaction(function () use ($payment, $action) {
            User::whereKey($payment->user_id)->lockForUpdate()->firstOrFail();
            $p = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

            return $action($p);
        });
    }

    private function reconcileLocked(Payment $p): array
    {
        if ($p->refund_reconciled_at) {
            return ['payment' => $p, 'review' => $p->refund_review_note];
        }
        $p->update(['status' => 'refunded']);
        $user = $p->user;
        $credits = $user->tokenTransactions()->where('payment_id', $p->id)->where('amount', '>', 0)
            ->where('status', 'posted')->get()->groupBy('token_type');
        foreach ($credits as $type => $entries) {
            $user->tokenTransactions()->create([
                'payment_id' => $p->id, 'token_type' => $type, 'kind' => 'refunded',
                'amount' => -(float) $entries->sum('amount'), 'status' => 'posted',
                'reference' => $p->reference, 'description' => 'Full payment refund · '.$p->reference,
            ]);
        }
        VacationTokenLot::where('user_id', $user->id)->where('payment_id', $p->id)->update(['status' => 'refunded']);

        $review = null;
        if ($p->type === Payment::TYPE_DUES && $p->payable_type === (new Membership)->getMorphClass()) {
            $m = Membership::whereKey($p->payable_id)->where('user_id', $user->id)->lockForUpdate()->first();
            if ($m) {
                $remaining = $m->payments()->where('status', 'succeeded')->where('period_end', '>', today())
                    ->orderByDesc('paid_at')->orderByDesc('id')->first();
                if ($remaining && in_array($remaining->membership_plan, ['associate', 'premium'], true) && in_array($remaining->billing_frequency, ['monthly', 'quarterly', 'biannual', 'annual'], true)) {
                    $m->update(['plan' => $remaining->membership_plan, 'monthly_fee' => config('membership.plans.'.$remaining->membership_plan.'.monthly_fee'),
                        'billing_frequency' => $remaining->billing_frequency, 'current_period_end' => $remaining->period_end,
                        'status' => 'active', 'billing_status' => 'active', 'expired_at' => null]);
                } elseif (! $remaining || ! $p->period_end || ! $m->current_period_end || $p->period_end->gte($m->current_period_end->copy()->startOfDay())) {
                    $this->memberships->expire($m);
                    MemberQrCode::where('user_id', $user->id)->update(['status' => 'inactive']);
                    if ($remaining) {
                        $review = 'Legacy paid coverage has no plan metadata; verify remaining entitlement before restoring benefits.';
                    }
                }
            }
        }
        if ($p->type === Payment::TYPE_PREMIUM && $p->payable_type === (new Policy)->getMorphClass()) {
            $policy = Policy::whereKey($p->payable_id)->where('user_id', $user->id)->lockForUpdate()->first();
            if ($policy) {
                $policy->increment('amount_due', (float) $p->amount);
                $policy->update(['due_on' => today()]);
            }
        }
        if ($p->type === Payment::TYPE_SUB_ACCOUNT) {
            $through = $user->payments()->where('type', Payment::TYPE_SUB_ACCOUNT)->where('status', 'succeeded')->max('period_end');
            $user->forceFill(['sub_accounts_paid_through' => $through])->save();
            if (! $through || \Illuminate\Support\Carbon::parse($through)->isPast()) {
                MemberQrCode::whereIn('user_id', $user->subAccounts()->select('id'))->update(['status' => 'inactive']);
                $review = 'Sub-account fee refunded; verify and suspend any dependent benefits already provisioned.';
            }
        }
        if (collect($this->ledger->balances($user))->contains(fn ($balance) => $balance < 0)) {
            $review = 'Refunded tokens were already spent/transferred; negative balance is retained for recovery review.';
        }
        $p->update(['refund_reconciled_at' => now(), 'refund_review_note' => $review]);

        return ['payment' => $p, 'review' => $review];
    }
}
