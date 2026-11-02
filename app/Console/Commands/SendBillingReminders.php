<?php

namespace App\Console\Commands;

use App\Models\Membership;
use App\Models\PaymentMethod;
use App\Models\Policy;
use App\Models\User;
use App\Notifications\MemberAlert;
use App\Services\MembershipService;
use Illuminate\Console\Command;

/**
 * Daily billing (SOW: recurring dues and payment notifications by email and SMS): renewal
 * reminders, expiring-card alerts, and renewals of memberships and Premium sub-account fees whose
 * paid period has ended, charged on the member's default card (declines retry daily until the
 * grace days run out).
 */
class SendBillingReminders extends Command
{
    protected $signature = 'billing:daily';

    protected $description = 'Charge due renewals and send renewal and card-expiry reminders';

    public function handle(MembershipService $memberships): int
    {
        $renewing = Membership::where('status', 'active')->where('monthly_fee', '>', 0)->where('auto_renew', true)
            ->whereDate('current_period_end', today()->addDays(7))->with('user')->get();
        foreach ($renewing as $membership) {
            $membership->user->notify(new MemberAlert('Membership', 'Your membership renews in 7 days',
                '$'.number_format($membership->periodAmount(), 2).' will be charged on '.$membership->current_period_end->format('M j, Y').'.', 'membership', mail: true));
        }

        // On the 1st: cards that expire at the end of this month
        $expiring = today()->day === 1
            ? PaymentMethod::where('type', 'card')->where('exp_year', now()->year)->where('exp_month', now()->month)->with('user')->get()
            : collect();
        foreach ($expiring as $method) {
            $method->user->notify(new MemberAlert('Payments', "Card ending {$method->last4} expires this month", 'Add a new card to avoid a failed payment.', 'methods', mail: true));
        }

        // Paid period over: charge the next one (or end cancelled memberships)
        $due = Membership::where('status', 'active')->where('monthly_fee', '>', 0)->where('plan', '!=', 'sub_account')
            ->where('current_period_end', '<=', now())->with('user')->get();
        foreach ($due as $membership) {
            $memberships->renew($membership);
        }

        $policies = $this->policyAlerts();

        $subAccountOwners = User::where('sub_accounts_paid_through', '<=', now())->get();
        foreach ($subAccountOwners as $owner) {
            $memberships->renewSubAccounts($owner);
        }

        $this->info("Reminders: {$renewing->count()} renewals, {$expiring->count()} cards, {$policies} policy alerts. Renewed: {$due->count()} memberships, {$subAccountOwners->count()} sub-account owners.");

        return self::SUCCESS;
    }

    /** SOW My Policies: alerts for upcoming expiration/renewal and premium payments; lapsed policies are marked expired. */
    private function policyAlerts(): int
    {
        $sent = 0;
        foreach ([30, 7] as $days) {
            foreach (Policy::where('status', 'active')->whereDate('expires_on', today()->addDays($days))->with('user')->get() as $policy) {
                $policy->user?->notify(new MemberAlert('Policy', "{$policy->type} policy renews in {$days} days",
                    "{$policy->policy_number} · {$policy->carrier} expires on {$policy->expires_on->format('M j, Y')}. Contact COSPARK to review your renewal.", 'policies', mail: true));
                $sent++;
            }
        }
        foreach (Policy::where('status', 'active')->where('amount_due', '>', 0)->whereDate('due_on', today()->addDays(7))->with('user')->get() as $policy) {
            $policy->user?->notify(new MemberAlert('Policy', 'Insurance payment due in 7 days',
                '$'.number_format($policy->amount_due, 2)." for {$policy->policy_number} is due on {$policy->due_on->format('M j, Y')}.", 'policies', mail: true));
            $sent++;
        }
        foreach (Policy::where('status', 'active')->whereDate('expires_on', '<', today())->with('user')->get() as $policy) {
            $policy->update(['status' => 'expired']);
            $policy->user?->notify(new MemberAlert('Policy', "{$policy->type} policy expired", "{$policy->policy_number} expired on {$policy->expires_on->format('M j, Y')}.", 'policies', mail: true));
            $sent++;
        }

        return $sent;
    }
}
