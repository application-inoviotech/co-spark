<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Claim;
use App\Models\MemberQrCode;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\QrRedemption;
use App\Models\User;
use App\Models\VacationRequest;
use App\Notifications\MemberAlert;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Local demo data: a Premium member with the history shown in the approved prototype.
 * Sign in with demo.member@cospark.test / DemoMember2026 (local environment only).
 */
class DemoMemberSeeder extends Seeder
{
    public const EMAIL = 'demo.member@cospark.test';

    public const PASSWORD = 'DemoMember2026';

    public function run(): void
    {
        if (User::where('email', self::EMAIL)->exists()) {
            return;
        }

        $start = today()->subMonths(23)->startOfMonth()->addDays(14);

        $user = User::create([
            'first_name' => 'James', 'last_name' => 'Carter', 'email' => self::EMAIL, 'phone' => '(248) 555-0142',
            'street' => '1280 Walton Blvd', 'city' => 'Rochester Hills', 'state' => 'MI', 'zip' => '48309',
            'role' => User::ROLE_MEMBER, 'status' => 'active', 'sms_opt_in' => true, 'sms_opt_in_at' => $start,
            'password' => self::PASSWORD,
        ]);
        $user->forceFill(['email_verified_at' => $start, 'created_at' => $start])->save();

        $membership = $user->memberships()->create([
            'plan' => 'premium', 'billing_frequency' => 'monthly', 'monthly_fee' => 25, 'status' => 'active',
            'auto_renew' => true, 'started_at' => $start, 'current_period_end' => $start->copy()->addMonths(24),
        ]);
        MemberQrCode::create(['user_id' => $user->id, 'code' => (string) Str::uuid(), 'status' => 'active']);

        $visa = $user->paymentMethods()->create(['type' => 'card', 'brand' => 'Visa', 'last4' => '4417', 'exp_month' => 8, 'exp_year' => now()->year + 2, 'is_default' => true]);
        $user->paymentMethods()->create(['type' => 'card', 'brand' => 'Mastercard', 'last4' => '8810', 'exp_month' => now()->subMonth()->month, 'exp_year' => now()->subMonth()->year]);
        $bank = $user->paymentMethods()->create(['type' => 'bank', 'bank_name' => 'Chase checking', 'last4' => '2291']);

        // Monthly dues history: one failed payment retried a few days later
        for ($m = 0, $month = $start->copy(); $month->lte(today()); $m++, $month->addMonth()) {
            if ($m === 13) {
                $this->payment($user, Payment::TYPE_DUES, 25, 'Premium membership · Monthly', 'Mastercard •8810', 'failed', $month, $membership);
                $retry = $this->payment($user, Payment::TYPE_DUES, 25, 'Retry · Premium membership', 'Visa •4417', 'succeeded', $month->copy()->addDays(3), $membership);
                $this->tokens($user, 'DT', 'earned', 12, 'Membership dues payment (retry)', $retry->reference, $retry->paid_at);

                continue;
            }
            $p = $this->payment($user, Payment::TYPE_DUES, 25, 'Premium membership · Monthly', 'Visa •4417', 'succeeded', $month, $membership);
            $this->tokens($user, 'DT', 'earned', 12, 'Membership dues payment', $p->reference, $p->paid_at);
        }

        $policies = [
            ['Home', 'CSP-HOM-220145', 'Farmers Insurance', 'James & Ana Carter', '1280 Walton Blvd, Rochester Hills, MI', '-5 months', 1860, 'Auto-renews', 930],
            ['Auto', 'CSP-AUT-448201', 'Farmers Insurance', 'James Carter', '2022 Toyota RAV4 · 2019 Honda Civic', '-11 months', 2140, 'Renewal due', 2140],
            ['Health', 'BCN-77310492', 'Partner carrier', 'Carter Family', 'Family plan · 4 members', '-8 months', 4200, 'Open enrollment Nov 1', 0],
            ['Umbrella', 'CSP-UMB-031177', 'Farmers Insurance', 'James Carter', '$1M personal liability', '-7 months', 380, 'Auto-renews', 0],
        ];
        foreach ($policies as [$type, $no, $carrier, $insured, $covers, $offset, $premium, $renewal, $due]) {
            $from = Carbon::parse($offset)->startOfMonth();
            $policy = $user->policies()->create([
                'type' => $type, 'policy_number' => $no, 'carrier' => $carrier, 'insured_name' => $insured, 'covers' => $covers,
                'starts_on' => $from, 'expires_on' => $from->copy()->addYear(), 'annual_premium' => $premium,
                'amount_due' => $due, 'due_on' => $due ? $from->copy()->addYear() : null, 'status' => 'active', 'renewal_note' => $renewal,
            ]);
            if ($due < $premium) {
                $paid = $this->payment($user, Payment::TYPE_PREMIUM, $premium - $due, "{$type} · {$no} · ".($due ? 'semi-annual' : 'annual'), $type === 'Health' ? 'Bank •2291' : 'Visa •4417', 'succeeded', $from, $policy);
                $it = (int) round($premium * 0.07);
                $this->tokens($user, 'IT', 'earned', $it, "{$type} policy · PY {$policy->policyYear()} · 7% of $".number_format($premium), $no, $from, $policy);
            }
        }

        // A renters policy cancelled mid-year: that year's IT was forfeited
        $renters = $user->policies()->create([
            'type' => 'Renters', 'policy_number' => 'CSP-REN-118820', 'carrier' => 'Farmers Insurance', 'insured_name' => 'James Carter',
            'starts_on' => today()->subMonths(18), 'expires_on' => today()->subMonths(6), 'annual_premium' => 600, 'status' => 'cancelled',
            'cancelled_at' => today()->subMonths(12),
        ]);
        $this->tokens($user, 'IT', 'earned', 42, "Renters policy · PY {$renters->policyYear()} · 7% of $600", $renters->policy_number, today()->subMonths(18), $renters, 'forfeited');
        $this->tokens($user, 'IT', 'forfeited', -42, 'Renters policy cancelled mid-year · current-year IT forfeited', $renters->policy_number, today()->subMonths(12));

        $vt = $this->payment($user, Payment::TYPE_VACATION_TOKENS, 500, '500 VT purchase', 'Visa •4417', 'succeeded', today()->subMonths(7));
        $this->tokens($user, 'VT', 'purchased', 500, 'Vacation Token purchase', $vt->reference, $vt->paid_at);
        $user->vacationTokenLots()->create(['payment_id' => $vt->id, 'lot_number' => 'VT-LOT-01', 'purchased_on' => $vt->paid_at, 'face_value' => 500]);

        $keys = Partner::where('slug', 'keys-cove-luxury-vacations')->first();
        $fish = Partner::where('slug', 'fish-camp')->first();
        if ($keys && $fish) {
            VacationRequest::create(['user_id' => $user->id, 'partner_id' => $keys->id, 'check_in' => today()->addDays(120), 'check_out' => today()->addDays(124), 'guests' => 4, 'tokens_applied' => 180, 'payment_amount' => 960, 'payment_status' => 'pending', 'status' => 'confirmed', 'notes' => 'Ground-floor unit if possible.', 'instructions' => 'Check-in from 4 PM. Door code sent 48 hours before arrival.']);
            $past = VacationRequest::create(['user_id' => $user->id, 'partner_id' => $fish->id, 'check_in' => today()->subMonths(16), 'check_out' => today()->subMonths(16)->addDays(3), 'guests' => 3, 'tokens_applied' => 90, 'payment_amount' => 0, 'payment_status' => 'paid', 'status' => 'completed']);
            $this->tokens($user, 'DT', 'used', -90, "Fish Camp stay · {$past->reference}", $past->reference, $past->check_in);
        }

        $eateries = Partner::where('slug', 'cospark-eateries')->first();
        $business = Partner::where('slug', 'cospark-business-partners')->first();
        foreach ([[$business, null, 10, 40], [$eateries, 'Rochester', 15, 12]] as [$partner, $location, $amount, $daysAgo]) {
            $r = QrRedemption::create(['user_id' => $user->id, 'partner_id' => $partner?->id, 'location' => $location, 'amount' => $amount]);
            $r->forceFill(['created_at' => today()->subDays($daysAgo)])->save();
            $this->tokens($user, 'DT', 'used', -$amount, 'QR redemption · '.$partner?->name, $r->reference, today()->subDays($daysAgo));
        }

        $auto = $user->policies()->where('type', 'Auto')->first();
        $claim = Claim::create([
            'user_id' => $user->id, 'policy_id' => $auto->id, 'first_name' => 'James', 'last_name' => 'Carter', 'email' => self::EMAIL,
            'phone' => $user->phone, 'preferred_contact' => 'Email', 'policy_number' => $auto->policy_number, 'insurance_type' => 'Auto',
            'claim_type' => 'Collision', 'incident_date' => today()->subDays(24), 'location' => 'Rochester Rd & Tienken Rd',
            'description' => 'Rear bumper damage in a low-speed collision.', 'status' => 'in_review', 'progress_step' => 1,
            'required_action' => 'Upload repair estimate', 'outstanding_documents' => ['Repair estimate'],
        ]);
        foreach ([
            [$user->id, $user->name, true, 'Photos uploaded from the scene and the rear bumper.', 21],
            [null, 'Adjuster · Dana Ruiz', false, 'Thanks for the photos. Please upload the body shop estimate so we can approve repairs.', 11],
        ] as [$authorId, $author, $fromMember, $body, $daysAgo]) {
            $claim->messages()->create(['user_id' => $authorId, 'author_name' => $author, 'from_member' => $fromMember, 'body' => $body])
                ->forceFill(['created_at' => today()->subDays($daysAgo)])->save();
        }

        Appointment::create(['type' => 'phone', 'preferred_date' => today()->addDays(6), 'preferred_time' => '10:30 AM', 'name' => $user->name, 'email' => self::EMAIL, 'phone' => $user->phone, 'reason' => 'Review my policies', 'status' => 'confirmed', 'user_id' => $user->id]);

        foreach ([
            ['Policy', 'Auto policy renews soon', 'Review your coverage before renewal.', 'policies'],
            ['Claims', "Action needed on {$claim->reference}", 'Upload your repair estimate.', 'claims'],
            ['Tokens', '+12 Dues Tokens credited', 'Membership payment received.', 'wallet'],
            ['Payments', 'Card ending 8810 has expired', 'Update or remove this payment method.', 'methods'],
        ] as [$cat, $title, $summary, $section]) {
            $user->notify(new MemberAlert($cat, $title, $summary, $section));
        }

        foreach ([['Ana Carter', 'ana.carter@cospark.test', 'Spouse / partner', ['Partner benefits', 'QR code', 'Vacations']], ['Leo Carter', 'leo.carter@cospark.test', 'Child (18+)', ['Partner benefits', 'QR code']]] as [$name, $email, $rel, $perms]) {
            [$first, $last] = explode(' ', $name);
            $sub = User::create(['first_name' => $first, 'last_name' => $last, 'email' => $email, 'role' => User::ROLE_MEMBER, 'status' => 'active', 'parent_id' => $user->id, 'relationship' => $rel, 'permissions' => $perms, 'password' => self::PASSWORD]);
            $sub->forceFill(['email_verified_at' => now()])->save();
            $sub->memberships()->create(['plan' => 'sub_account', 'monthly_fee' => 8.88, 'status' => 'active', 'started_at' => now()]);
        }
    }

    private function payment(User $user, string $type, float $amount, string $desc, string $method, string $status, Carbon $date, $payable = null): Payment
    {
        $payment = $user->payments()->create([
            'payable_type' => $payable?->getMorphClass(), 'payable_id' => $payable?->getKey(), 'type' => $type, 'description' => $desc,
            'amount' => $amount, 'status' => $status, 'method_label' => $method, 'gateway' => 'test',
            'failure_reason' => $status === 'failed' ? 'Your card ending 8810 has expired.' : null,
            'paid_at' => $status === 'succeeded' ? $date : null,
        ]);
        $payment->forceFill(['created_at' => $date])->save();

        return $payment;
    }

    private function tokens(User $user, string $type, string $kind, float $amount, string $desc, string $ref, $date, ?Policy $policy = null, string $status = 'posted'): void
    {
        $t = $user->tokenTransactions()->create([
            'token_type' => $type, 'kind' => $kind, 'amount' => $amount, 'description' => $desc, 'reference' => $ref, 'status' => $status,
            'policy_id' => $policy?->id, 'policy_year' => $policy?->policyYear(),
        ]);
        $t->forceFill(['created_at' => $date])->save();
    }
}
