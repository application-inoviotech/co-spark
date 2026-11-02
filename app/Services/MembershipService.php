<?php

namespace App\Services;

use App\Models\MemberQrCode;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\MemberAlert;
use App\Services\Billing\AuthorizeNet;
use App\Services\Billing\BillingException;
use App\Services\Billing\PaymentDeclined;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Membership lifecycle and dues billing on Authorize.net saved cards. Each successful payment buys
 * one billing period; the daily billing job (billing:daily) renews it on the member's default card.
 * SOW: paid memberships activate after email verification AND a successful payment;
 * DT is credited only for successful dues payments (Associate 4 / Premium 12 per month);
 * failed payments are recorded and notified; expired memberships lose paid benefits.
 */
class MembershipService
{
    public function __construct(
        private TokenLedger $ledger,
        private AuthorizeNet $gateway,
    ) {}

    public function start(User $user, string $plan, string $frequency = 'monthly'): Membership
    {
        return $user->memberships()->create([
            'plan' => $plan,
            'billing_frequency' => $frequency,
            'monthly_fee' => config("membership.plans.{$plan}.monthly_fee"),
            'status' => 'pending',
        ]);
    }

    /** Called once the email is verified: free memberships are active straight away. */
    public function activateIfFree(Membership $membership): void
    {
        if ($membership->isPaid()) {
            return;
        }

        DB::transaction(function () use ($membership) {
            $membership->update(['status' => 'active', 'started_at' => now()]);
            $membership->user->update(['status' => 'active']);
        });
    }

    /** True while a paid period is running and nothing is outstanding. */
    public function isPaidUp(Membership $membership): bool
    {
        return $membership->status === 'active' && $membership->billing_status === 'active' && $membership->current_period_end?->isFuture();
    }

    /** First payment, or settling an overdue renewal: a new period starts today. */
    public function payDues(Membership $membership, string $frequency, PaymentMethod $method): Payment
    {
        if ($this->isPaidUp($membership)) {
            throw ValidationException::withMessages(['payment' => 'Your dues are paid until '.$membership->current_period_end->format('M j, Y').' and renew automatically.']);
        }

        return $this->chargePeriod($membership, $membership->plan, $frequency, $method, now());
    }

    /** Move to a higher plan now: a new monthly period starts today and is charged immediately. */
    public function upgrade(Membership $membership, string $plan, PaymentMethod $method): Payment
    {
        $payment = $this->chargePeriod($membership, $plan, 'monthly', $method, now());
        if ($payment->status === 'succeeded') {
            $membership->update(['cancelled_at' => null, 'auto_renew' => true]);
        }

        return $payment;
    }

    /** Lower plan or Free: takes effect at the next renewal. */
    public function scheduleSwitch(Membership $membership, string $plan): void
    {
        $membership->update(['pending_plan' => $plan] + ($plan === 'free' ? ['auto_renew' => false] : []));
    }

    /** The new frequency applies from the next renewal. */
    public function changeFrequency(Membership $membership, string $frequency): void
    {
        $membership->update(['billing_frequency' => $frequency]);
    }

    /** Membership stays active until the end of the paid period, then ends. */
    public function cancel(Membership $membership): void
    {
        $membership->update(['cancelled_at' => now(), 'auto_renew' => false, 'pending_plan' => 'free']);

        $this->alert($membership->user, new MemberAlert(
            'Membership', 'Membership cancellation scheduled',
            'Your membership stays active until '.($membership->current_period_end?->format('M j, Y') ?? 'the end of the period').'.',
            'membership', mail: true,
        ));
    }

    /**
     * Daily billing job, for a paid membership whose period has ended: charge the next period on the
     * default card (pending plan changes apply now). A decline is retried once a day; after the grace
     * days the membership expires. Cancelled memberships and switches to Free end here.
     */
    public function renew(Membership $membership): void
    {
        DB::transaction(function () use ($membership) {
            User::whereKey($membership->user_id)->lockForUpdate()->firstOrFail();
            Membership::whereKey($membership->id)->lockForUpdate()->firstOrFail();
            $membership->refresh();
            if ($membership->status !== 'active' || ! $membership->isPaid() || ! $membership->current_period_end || $membership->current_period_end->isFuture()) {
                return;
            }
            $this->renewDue($membership);
        });
    }

    private function renewDue(Membership $membership): void
    {
        if (! $membership->auto_renew || $membership->pending_plan === 'free') {
            $this->expire($membership);

            return;
        }

        $triedToday = $membership->payments()->where('status', 'failed')->where('created_at', '>=', today())->exists();
        if (! $triedToday) {
            $method = $membership->user->paymentMethods()->where('is_default', true)->first();
            $payment = $this->chargePeriod($membership, $membership->pending_plan ?? $membership->plan, $membership->billing_frequency, $method, $membership->current_period_end);
            if ($payment->status === 'succeeded') {
                return;
            }
        }

        if ($membership->current_period_end->copy()->addDays(config('cospark.billing_grace_days'))->isPast()) {
            $this->expire($membership);
        }
    }

    /** Paid benefits stop until the member renews. */
    public function expire(Membership $membership): void
    {
        if (in_array($membership->status, ['expired', 'cancelled'], true)) {
            return;
        }

        $cancelledByMember = $membership->cancelled_at !== null;
        $membership->update(['status' => $cancelledByMember ? 'cancelled' : 'expired', 'expired_at' => now(), 'billing_status' => 'canceled', 'auto_renew' => false]);
        MemberQrCode::where('user_id', $membership->user_id)->update(['status' => 'inactive']);

        $planName = config("membership.plans.{$membership->plan}.name");
        $this->alert($membership->user, new MemberAlert('Membership', "Your {$planName} membership has ".($cancelledByMember ? 'ended' : 'expired'),
            'Token earning, QR benefits and vacation planning are paused. Renew any time from your dashboard.', 'membership', mail: true));
    }

    /** Active, non-suspended sub-accounts the Premium member pays for. */
    public function subAccountCount(User $owner): int
    {
        return $owner->subAccounts()->where('status', '!=', 'suspended')->count();
    }

    /**
     * Premium sub-account fees ($8.88/month each) on the main member's default card. The first
     * sub-account starts a monthly cycle charged now; later additions are billed from the next charge.
     */
    public function syncSubAccounts(User $owner): void
    {
        if ($this->subAccountCount($owner) === 0) {
            $owner->forceFill(['sub_accounts_paid_through' => null])->save();

            return;
        }
        if ($owner->sub_accounts_paid_through?->isFuture()) {
            return;
        }

        $method = $owner->paymentMethods()->where('is_default', true)->first()
            ?? throw ValidationException::withMessages(['email' => 'Add a payment method before adding sub-accounts.']);
        $payment = $this->chargeSubAccounts($owner, $method, now());
        if ($payment->status !== 'succeeded') {
            throw ValidationException::withMessages(['email' => $payment->failure_reason]);
        }
    }

    /** Daily billing job: the next month of sub-account fees. Unpaid past the grace days, sub-accounts are suspended. */
    public function renewSubAccounts(User $owner): void
    {
        if ($this->subAccountCount($owner) === 0) {
            $owner->forceFill(['sub_accounts_paid_through' => null])->save();

            return;
        }

        $triedToday = $owner->payments()->where('type', Payment::TYPE_SUB_ACCOUNT)->where('status', 'failed')->where('created_at', '>=', today())->exists();
        if (! $triedToday) {
            $method = $owner->paymentMethods()->where('is_default', true)->first();
            if ($this->chargeSubAccounts($owner, $method, $owner->sub_accounts_paid_through)->status === 'succeeded') {
                return;
            }
        }

        if ($owner->sub_accounts_paid_through->copy()->addDays(config('cospark.billing_grace_days'))->isPast()) {
            foreach ($owner->subAccounts()->where('status', '!=', 'suspended')->get() as $sub) {
                $sub->update(['status' => 'suspended']);
                $sub->membership?->update(['status' => 'cancelled']);
            }
            $owner->forceFill(['sub_accounts_paid_through' => null])->save();
            $this->alert($owner, new MemberAlert('Payments', 'Sub-accounts suspended', 'The sub-account fees could not be collected. Update your card and reactivate them from your dashboard.', 'subaccounts', mail: true));
        }
    }

    /** Charge one billing period of a plan and record the result (success or failure). */
    private function chargePeriod(Membership $membership, string $plan, string $frequency, ?PaymentMethod $method, Carbon $periodStart): Payment
    {
        $amount = round((float) config("membership.plans.{$plan}.monthly_fee") * config("membership.frequencies.{$frequency}.months"), 2);
        $description = config("membership.plans.{$plan}.name").' membership · '.config("membership.frequencies.{$frequency}.label");

        try {
            $transactionId = $this->gateway->charge($method ?? throw new PaymentDeclined('No default card on file.'), $amount, $description, "M{$membership->id}-".now()->format('ymdHi'));
        } catch (BillingException $e) {
            if (! $e instanceof PaymentDeclined) {
                report($e);
            }
            if ($membership->status === 'active') {
                $membership->update(['billing_status' => 'past_due']);
            }

            return $this->recordFailedDues($membership, $method, $e instanceof PaymentDeclined ? $e->getMessage() : 'The payment could not be processed. Please try again.', $amount, $description);
        }

        return $this->recordOrVoid($transactionId, fn () => $this->recordDues($membership, $plan, $frequency, $amount, $description, $transactionId, $method, $periodStart));
    }

    /**
     * Saves a successful charge; if that fails (e.g. a database error) the charge is voided at
     * Authorize.net so a member is never billed without a payment record.
     */
    private function recordOrVoid(string $transactionId, callable $record): Payment
    {
        try {
            return $record();
        } catch (\Throwable $e) {
            report($e);
            try {
                $this->gateway->refund($transactionId);
                $message = 'We couldn\'t complete your payment, so the charge was cancelled. Please try again.';
            } catch (\Throwable $voidError) {
                Log::critical("Authorize.net charge {$transactionId} succeeded but could not be recorded or voided. Refund it manually.", ['exception' => $voidError]);
                $message = 'We couldn\'t complete your payment. Please contact COSPARK before trying again.';
            }

            throw ValidationException::withMessages(['payment_method' => $message]);
        }
    }

    private function recordDues(Membership $membership, string $plan, string $frequency, float $amount, string $description, string $transactionId, PaymentMethod $method, Carbon $periodStart): Payment
    {
        $user = $membership->user;
        $periodEnd = $periodStart->copy()->addMonths(config("membership.frequencies.{$frequency}.months"));
        $planName = config("membership.plans.{$plan}.name");

        [$payment, $tokens] = DB::transaction(function () use ($membership, $user, $plan, $frequency, $amount, $description, $transactionId, $method, $periodStart, $periodEnd, $planName) {
            $payment = $user->payments()->create([
                'payable_type' => $membership->getMorphClass(), 'payable_id' => $membership->id, 'type' => Payment::TYPE_DUES,
                'description' => $description, 'amount' => $amount, 'status' => 'succeeded', 'method_label' => $method->label(),
                'payment_method_id' => $method->id, 'gateway' => 'authorizenet', 'gateway_reference' => $transactionId,
                'period_start' => $periodStart, 'period_end' => $periodEnd, 'paid_at' => now(),
                'membership_plan' => $plan, 'billing_frequency' => $frequency,
            ]);

            $membership->update([
                'plan' => $plan, 'billing_frequency' => $frequency, 'monthly_fee' => config("membership.plans.{$plan}.monthly_fee"),
                'pending_plan' => $membership->pending_plan === $plan ? null : $membership->pending_plan,
                'status' => 'active', 'started_at' => $membership->started_at ?? now(), 'current_period_end' => $periodEnd,
                'billing_status' => 'active', 'expired_at' => null,
            ]);
            $user->update(['status' => 'active']);
            MemberQrCode::firstOrCreate(['user_id' => $user->id], ['code' => (string) Str::uuid()])->update(['status' => 'active']);

            $tokens = config("membership.plans.{$plan}.dues_tokens") * config("membership.frequencies.{$frequency}.months");
            if ($tokens > 0) {
                $this->ledger->credit($user, TokenTransaction::DUES, $tokens, 'earned', "{$planName} membership dues · {$periodStart->format('M Y')}", $payment, $payment->reference);
            }

            return [$payment, $tokens];
        });

        // After the commit: the card is already charged, so the record must not depend on the email going out
        $this->alert($user, new MemberAlert('Payments', 'Payment received · $'.number_format($payment->amount, 2),
            "{$payment->description}. Paid through {$periodEnd->format('M j, Y')}.".($tokens ? " +{$tokens} Dues Tokens credited." : ''), 'payments', mail: true));

        return $payment;
    }

    /** Notifications are best-effort: a mail or SMS outage must never undo a recorded payment. */
    private function alert(User $user, MemberAlert $alert): void
    {
        try {
            $user->notify($alert);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    private function recordFailedDues(Membership $membership, ?PaymentMethod $method, string $reason, float $amount, string $description): Payment
    {
        $payment = $membership->user->payments()->create([
            'payable_type' => $membership->getMorphClass(), 'payable_id' => $membership->id, 'type' => Payment::TYPE_DUES,
            'description' => $description, 'amount' => $amount, 'status' => 'failed', 'method_label' => $method?->label(),
            'payment_method_id' => $method?->id, 'gateway' => 'authorizenet', 'failure_reason' => $reason,
        ]);

        $this->alert($membership->user, new MemberAlert('Payments', 'Membership payment failed', "{$reason} Update your card or retry from your dashboard. No tokens were issued.", 'payments', mail: true));

        return $payment;
    }

    private function chargeSubAccounts(User $owner, ?PaymentMethod $method, Carbon $periodStart): Payment
    {
        $quantity = $this->subAccountCount($owner);
        $fee = (float) config('membership.sub_account_fee');
        $amount = round($quantity * $fee, 2);
        $description = "Sub-accounts · {$quantity} × $".number_format($fee, 2);
        $periodEnd = $periodStart->copy()->addMonth();

        try {
            $transactionId = $this->gateway->charge($method ?? throw new PaymentDeclined('No default card on file.'), $amount, $description, "S{$owner->id}-".now()->format('ymdHi'));
        } catch (BillingException $e) {
            if (! $e instanceof PaymentDeclined) {
                report($e);
            }
            $reason = $e instanceof PaymentDeclined ? $e->getMessage() : 'The payment could not be processed. Please try again.';
            $payment = $owner->payments()->create([
                'type' => Payment::TYPE_SUB_ACCOUNT, 'description' => $description, 'amount' => $amount, 'status' => 'failed',
                'method_label' => $method?->label(), 'payment_method_id' => $method?->id, 'gateway' => 'authorizenet', 'failure_reason' => $reason,
            ]);
            $this->alert($owner, new MemberAlert('Payments', 'Sub-account payment failed', "{$reason} Update your card to keep your sub-accounts active.", 'payments', mail: true));

            return $payment;
        }

        $payment = $this->recordOrVoid($transactionId, fn () => DB::transaction(function () use ($owner, $description, $amount, $method, $transactionId, $periodStart, $periodEnd) {
            $payment = $owner->payments()->create([
                'type' => Payment::TYPE_SUB_ACCOUNT, 'description' => $description, 'amount' => $amount, 'status' => 'succeeded',
                'method_label' => $method->label(), 'payment_method_id' => $method->id, 'gateway' => 'authorizenet', 'gateway_reference' => $transactionId,
                'paid_at' => now(), 'period_start' => $periodStart, 'period_end' => $periodEnd,
            ]);
            $owner->forceFill(['sub_accounts_paid_through' => $periodEnd])->save();

            return $payment;
        }));
        $this->alert($owner, new MemberAlert('Payments', 'Payment received · $'.number_format($amount, 2), "{$description}. Paid through {$periodEnd->format('M j, Y')}.", 'payments', mail: true));

        return $payment;
    }
}
