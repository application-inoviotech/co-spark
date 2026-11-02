<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Producer;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\MemberAlert;
use App\Services\ProducerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Local demo producers matching the approved prototype (local / staging only):
 *  - producer.demo@cospark.test / DemoProducer2026 — approved, ~7 months writing
 *  - producer.review@cospark.test / DemoProducer2026 — onboarding, under review
 */
class DemoProducerSeeder extends Seeder
{
    public const PASSWORD = 'DemoProducer2026';

    public function run(ProducerService $producers): void
    {
        if (User::where('email', 'producer.demo@cospark.test')->exists()) {
            return;
        }

        $joined = today()->subMonths(7)->startOfMonth()->addDay();
        $producer = $this->producer('Daniel', 'Brooks', 'producer.demo@cospark.test', '(248) 555-0177', 'active', $joined, [
            'npn' => '19934021', 'business_name' => 'Brooks Insurance Services', 'business_address' => '220 W University Dr, Rochester, MI 48307',
            'payout_label' => 'Direct deposit · Chase •5520',
        ]);

        $producer->licenses()->create(['license_type' => 'Property & Casualty', 'license_number' => '0901234', 'state' => 'MI', 'issued_on' => '2021-04-12', 'expires_on' => today()->addYears(2), 'verified_at' => $joined]);
        $producer->licenses()->create(['license_type' => 'Life & Health', 'license_number' => '0901234', 'state' => 'MI', 'issued_on' => '2022-08-01', 'expires_on' => today()->addDays(75), 'verified_at' => $joined]);

        foreach (['Resume', 'Insurance license — P&C', 'Completed producer application'] as $name) {
            $producer->documents()->create(['name' => $name, 'status' => 'verified', 'uploaded_at' => $joined->copy()->subDays(20)]);
        }
        $producer->documents()->create(['name' => 'E&O certificate', 'status' => 'needed']);

        // Commissions: a few policies each month since joining; the latest month is still pending
        $lines = [['Auto', 1850], ['Home', 1620], ['Business', 6400], ['Life', 980], ['Renters', 240], ['Auto', 2210], ['Condo', 760], ['Commercial Auto', 5200]];
        $insureds = ['R. Alvarez', 'J. Kim', 'Northside Bakery LLC', 'T. Nguyen', 'O. Miller', 'S. Patel', 'L. Romero', 'Great Lakes Haulers'];
        $n = 0;
        for ($month = $joined->copy(); $month->lt(today()->startOfMonth()); $month->addMonth()) {
            $m = $month->month;
            for ($k = 0; $k < 3 + ($m % 3); $k++) {
                [$line, $base] = $lines[($m + $k) % count($lines)];
                $premium = round($base * (0.85 + (($m * 7 + $k * 13) % 30) / 100));
                $commission = $producers->recordCommission($producer, [
                    'reference' => 'POL-26'.(4100 + $n++), 'insured' => $insureds[($m * 2 + $k) % count($insureds)], 'line' => $line,
                    'written_on' => $month->copy()->addDays(2 + $k * 6), 'premium' => $premium, 'commission_base' => round($premium * 0.12, 2),
                ]);
                if ($month->copy()->addMonth()->lt(today()->startOfMonth())) {
                    $commission->update(['status' => 'paid', 'paid_on' => $month->copy()->addMonth()->startOfMonth()]);
                }
            }
        }

        $leads = [
            ['Maria', 'Gonzalez', 'maria.g@example.com', '(248) 555-0120', 'auto-home', 'quote', 'new', null, 3],
            ['Kevin', 'Park', 'kpark@example.com', '(586) 555-0199', 'business', 'appointment', 'contacted', 'Restaurant, 12 employees. Wants GL + WC.', 6],
            ['Tasha', 'Reed', 'treed@example.com', '(248) 555-0164', 'life', 'referral', 'quoted', '20-yr term, $500k.', 12],
            ['Owen', 'Miller', 'omiller@example.com', '(313) 555-0147', 'renters', 'quote', 'won', 'Bound last month.', 19],
            ['Priya', 'Shah', 'pshah@example.com', '(248) 555-0102', 'auto', 'member referral', 'lost', 'Stayed with current carrier.', 26],
        ];
        foreach ($leads as [$first, $last, $email, $phone, $type, $source, $status, $note, $daysAgo]) {
            Lead::create([
                'source' => $source, 'insurance_type' => $type, 'zip' => '48307', 'first_name' => $first, 'last_name' => $last,
                'email' => $email, 'phone' => $phone, 'status' => $status, 'producer_notes' => $note,
                'producer_id' => $producer->id, 'assigned_at' => today()->subDays($daysAgo),
            ]);
        }

        foreach ([[2, '10:30 AM', 'phone', 'Maria Gonzalez', 'New insurance quote', 'Auto & home bundle quote', 'confirmed'], [6, '1:00 PM', 'in_person', 'Kevin Park', 'New insurance quote', 'Business coverage review', 'confirmed'], [13, '9:00 AM', 'phone', 'Tasha Reed', 'New insurance quote', 'Term life application', 'pending']] as [$inDays, $time, $type, $who, $reason, $message, $status]) {
            Appointment::create(['type' => $type, 'preferred_date' => today()->addDays($inDays), 'preferred_time' => $time, 'name' => $who, 'email' => Str::slug($who, '.').'@example.com', 'phone' => '(248) 555-0100', 'reason' => $reason, 'message' => $message, 'status' => $status, 'producer_id' => $producer->id]);
        }

        $producers->syncBonus($producer->refresh());

        foreach ([
            ['Documents', 'Upload your E&O certificate', 'Required to complete onboarding.', 'documents'],
            ['Leads', 'New lead assigned · Maria Gonzalez', 'Auto & Home · Website quote', 'leads'],
            ['Commission', 'Last month’s commission paid', 'Deposited to your account on file.', 'commission'],
            ['Licensing', 'Life & Health license expires soon', 'Renew to keep life lines active.', 'licensing'],
        ] as [$cat, $title, $summary, $section]) {
            $producer->user->notify(new MemberAlert($cat, $title, $summary, $section, portal: 'producer'));
        }

        // A second producer still in onboarding (portal shows the "under review" state)
        $review = $this->producer('Alicia', 'Moreno', 'producer.review@cospark.test', '(248) 555-0181', 'under_review', today()->subDays(9));
        $review->documents()->create(['name' => 'Resume', 'status' => 'verified', 'uploaded_at' => today()->subDays(12)]);
        $review->documents()->create(['name' => 'E&O certificate', 'status' => 'needed']);
    }

    private function producer(string $first, string $last, string $email, string $phone, string $status, $joined, array $extra = []): Producer
    {
        $user = User::create([
            'first_name' => $first, 'last_name' => $last, 'email' => $email, 'phone' => $phone, 'city' => 'Rochester', 'state' => 'MI', 'zip' => '48307',
            'role' => User::ROLE_PRODUCER, 'status' => 'active', 'password' => self::PASSWORD,
        ]);
        $user->forceFill(['email_verified_at' => $joined])->save();

        // The required $8.88 Associate membership, paid up
        $membership = $user->memberships()->create([
            'plan' => 'associate', 'billing_frequency' => 'monthly', 'monthly_fee' => 8.88, 'status' => 'active',
            'started_at' => $joined, 'current_period_end' => today()->addDays(20),
        ]);
        $payment = $user->payments()->create([
            'payable_type' => $membership->getMorphClass(), 'payable_id' => $membership->id, 'type' => Payment::TYPE_DUES,
            'description' => 'Associate membership · Monthly', 'amount' => 8.88, 'status' => 'succeeded', 'method_label' => 'Visa •4417',
            'gateway' => 'test', 'paid_at' => today()->subDays(10),
        ]);
        $user->tokenTransactions()->create(['token_type' => TokenTransaction::DUES, 'kind' => 'earned', 'amount' => 4, 'description' => 'Associate membership dues', 'reference' => $payment->reference, 'payment_id' => $payment->id]);

        return Producer::create([
            'user_id' => $user->id, 'producer_number' => 'PRD-26'.Str::upper(Str::random(5)), 'status' => $status,
            'onboarded_on' => $status === 'active' ? $joined : null, 'qualification_started_on' => $status === 'active' ? $joined : null,
            ...$extra,
        ]);
    }
}
