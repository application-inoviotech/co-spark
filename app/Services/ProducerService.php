<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\Producer;
use App\Models\ProducerApplication;
use App\Models\User;
use App\Notifications\MemberAlert;
use App\Notifications\NewSubmission;
use App\Notifications\ProducerWelcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Producer onboarding, bonus tracking and commissions.
 * SOW: once approved the applicant is onboarded as a Producer and is emailed their sign-in details;
 * the 2,000-token bonus is credited once licensing and the $8.88 membership are in place,
 * and can be redeemed after 12 months of writing insurance with COSPARK.
 */
class ProducerService
{
    public function __construct(
        private MembershipService $memberships,
    ) {}

    /** Admin approval of an application: create the producer account and onboarding records. */
    public function approve(ProducerApplication $application): Producer
    {
        $password = null; // only a new account gets a temporary password; an existing member keeps theirs
        $producer = DB::transaction(function () use ($application, &$password) {
            ProducerApplication::whereKey($application->id)->lockForUpdate()->firstOrFail();
            $application->refresh();
            abort_if(in_array($application->status, ['approved', 'rejected'], true), 422, 'This application has already been processed.');
            $existing = User::where('email', strtolower($application->email))->first();
            if ($existing && ($existing->isAdmin() || $existing->isSubAccount() || $existing->producer)) {
                throw ValidationException::withMessages(['email' => 'This account cannot be onboarded from this application.']);
            }
            $user = User::firstOrCreate(['email' => strtolower($application->email)], [
                'first_name' => $application->first_name,
                'last_name' => $application->last_name,
                'phone' => $application->phone,
                'street' => $application->street,
                'city' => $application->city,
                'state' => $application->state,
                'zip' => $application->zip,
                'status' => 'active',
                'password' => $temporary = Str::password(12, symbols: false),
            ]);
            if ($user->wasRecentlyCreated) {
                $password = $temporary;
            }
            $user->update(['role' => User::ROLE_PRODUCER, 'status' => 'active']);

            if (! $user->membership || ! $user->membership->isPaid()) {
                $this->memberships->start($user, 'associate'); // the required $8.88 membership
            }

            $producer = Producer::create([
                'user_id' => $user->id,
                'producer_application_id' => $application->id,
                'producer_number' => $application->reference,
                'status' => 'active',
                'onboarded_on' => today(),
                'qualification_started_on' => null, // qualification begins with the first written policy
            ]);

            if ($application->license_status === 'Licensed') {
                $producer->licenses()->create([
                    'license_type' => $application->license_type,
                    'license_number' => $application->license_number,
                    'state' => $application->license_state,
                    'issued_on' => $application->license_issued_on,
                    'expires_on' => $application->license_expires_on ?? today()->addYears(2),
                    'verified_at' => now(), // verified during the application review
                ]);
            }

            foreach ($application->attachments as $file) {
                $producer->documents()->create(['name' => $file->original_name, 'path' => $file->path, 'status' => 'verified', 'uploaded_at' => $file->created_at]);
            }

            $application->update(['status' => 'approved']);

            return $producer;
        });

        $producer->user->notify(new ProducerWelcome($password));
        $producer->user->notify(new MemberAlert('Producer', 'Welcome to COSPARK, producer', 'Activate your $8.88 membership to qualify for the producer token bonus.', 'overview', portal: 'producer'));

        return $this->syncBonus($producer);
    }

    /** Credit / release the bonus as the conditions are met. Safe to call repeatedly. */
    public function syncBonus(Producer $producer): Producer
    {
        $producer->refresh()->load('licenses', 'user.membership');
        $eligible = $producer->bonusEligibility();

        if ($producer->bonus_status === 'not_credited' && $eligible['approved'] && $eligible['licensed'] && $eligible['membership']) {
            $producer->update(['bonus_status' => 'locked', 'bonus_credited_on' => today(), 'bonus_tokens' => Producer::BONUS_TOKENS]);
            $producer->user->notify(new MemberAlert('Tokens', '2,000-token producer bonus credited', 'Redeemable after 12 months of writing insurance with COSPARK.', 'tokens', portal: 'producer'));
        }

        if ($producer->bonus_status === 'locked' && ! in_array(false, $eligible, true)) {
            $producer->update(['bonus_status' => 'available']);
            $producer->user->notify(new MemberAlert('Tokens', 'Your producer bonus is now redeemable', '12-month qualification complete.', 'tokens', mail: true, portal: 'producer'));
        }

        return $producer;
    }

    public function requestRedemption(Producer $producer): void
    {
        DB::transaction(function () use ($producer) {
            Producer::whereKey($producer->id)->lockForUpdate()->firstOrFail();
            $this->syncBonus($producer);
            if ($producer->bonus_status !== 'available' || in_array(false, $producer->bonusEligibility(), true)) {
                throw ValidationException::withMessages(['bonus' => 'The bonus is not redeemable yet. Active licensing, membership and the qualification period are required.']);
            }
            $producer->update(['bonus_status' => 'redemption_requested', 'bonus_redemption_requested_at' => now()]);
        });

        Notification::route('mail', config('cospark.admin_notify_email'))->notify(new NewSubmission(
            'producer bonus redemption', $producer->producer_number,
            ['Producer' => $producer->user->name, 'Tokens' => number_format($producer->bonus_tokens)],
        ));
    }

    /** Record commission on a written policy at the producer's rate (75% of the commission base). */
    public function recordCommission(Producer $producer, array $policy): Commission
    {
        $commission = $producer->commissions()->create([
            'policy_reference' => $policy['reference'],
            'insured_name' => $policy['insured'],
            'line_of_business' => $policy['line'],
            'written_on' => $policy['written_on'],
            'premium' => $policy['premium'],
            'commission_base' => $policy['commission_base'],
            'rate' => $producer->commission_rate,
            'amount' => round($policy['commission_base'] * $producer->commission_rate, 2),
            'status' => 'pending',
        ]);

        if (! $producer->qualification_started_on) {
            $producer->update(['qualification_started_on' => $commission->written_on]);
        }

        return $commission;
    }
}
