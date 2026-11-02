<?php

namespace Tests\Feature;

use App\Models\Membership;
use App\Models\Payment;
use App\Models\User;
use App\Services\Billing\RefundReconciler;
use App\Services\MemberDashboardPresenter;
use App\Services\MembershipService;
use App\Services\TokenLedger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class RefundReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('session.driver', 'array');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Notification::fake();
        config(['services.authorizenet.sandbox' => true]);
    }

    private function member(): User
    {
        $user = User::create(['first_name' => 'QA', 'last_name' => 'Refund', 'email' => Str::uuid().'@example.invalid', 'password' => 'Test-only!26', 'role' => 'member', 'status' => 'active']);
        $user->forceFill(['gateway_customer_id' => (string) (10000 + $user->id)])->save();

        return $user;
    }

    private function payment(User $u, array $attributes = []): Payment
    {
        return $u->payments()->create($attributes + ['type' => Payment::TYPE_VACATION_TOKENS, 'description' => 'QA', 'amount' => 25, 'status' => 'refunded', 'gateway' => 'authorizenet', 'gateway_reference' => (string) random_int(100000, 999999)]);
    }

    public function test_full_refund_reverses_linked_rewards_and_removes_vt_lot_once(): void
    {
        $u = $this->member();
        $p = $this->payment($u);
        app(TokenLedger::class)->credit($u, 'VT', 25, 'purchased', 'QA', $p);
        $lot = $u->vacationTokenLots()->create(['payment_id' => $p->id, 'lot_number' => 'QA-LOT', 'purchased_on' => today()->subMonths(2), 'face_value' => 25]);
        $result = app(RefundReconciler::class)->confirmed($p);
        $this->assertNull($result['review']);
        $this->assertSame(0.0, app(TokenLedger::class)->balance($u, 'VT'));
        $this->assertSame('refunded', $lot->fresh()->status);
        $this->assertSame(0.0, $lot->fresh()->accruedValue());
        app(RefundReconciler::class)->confirmed($p);
        $this->assertSame(1, $u->tokenTransactions()->where('kind', 'refunded')->count());
        $this->assertSame([], app(MemberDashboardPresenter::class)->present($u->fresh())['vt']);
        Http::assertNothingSent();
    }

    public function test_refunded_dues_revoke_paid_membership_and_qr_without_reversing_other_payment(): void
    {
        $u = $this->member();
        $m = $u->memberships()->create(['plan' => 'premium', 'monthly_fee' => 25, 'status' => 'active', 'billing_status' => 'active', 'auto_renew' => true, 'current_period_end' => now()->addMonth()]);
        $u->qrCode()->create(['code' => (string) Str::uuid(), 'status' => 'active']);
        $p = $this->payment($u, ['type' => Payment::TYPE_DUES, 'payable_type' => $m->getMorphClass(), 'payable_id' => $m->id, 'period_end' => today()->addMonth()]);
        app(TokenLedger::class)->credit($u, 'DT', 12, 'earned', 'QA', $p);
        $other = $this->payment($u, ['status' => 'succeeded']);
        app(TokenLedger::class)->credit($u, 'VT', 25, 'purchased', 'QA', $other);
        app(RefundReconciler::class)->confirmed($p);
        $this->assertSame('expired', $m->fresh()->status);
        $this->assertFalse($m->fresh()->auto_renew);
        $this->assertSame('inactive', $u->qrCode()->first()->status);
        $this->assertSame(0.0, app(TokenLedger::class)->balance($u, 'DT'));
        $this->assertSame(25.0, app(TokenLedger::class)->balance($u, 'VT'));
    }

    public function test_refunding_upgrade_restores_other_successful_plan_and_its_paid_period(): void
    {
        $u = $this->member();
        $m = $u->memberships()->create(['plan' => 'premium', 'monthly_fee' => 25, 'status' => 'active', 'current_period_end' => now()->addMonth()]);
        $older = $this->payment($u, ['type' => Payment::TYPE_DUES, 'payable_type' => $m->getMorphClass(), 'payable_id' => $m->id, 'status' => 'succeeded', 'amount' => 8.88, 'membership_plan' => 'associate', 'billing_frequency' => 'monthly', 'period_end' => today()->addDays(20)]);
        $refunded = $this->payment($u, ['type' => Payment::TYPE_DUES, 'payable_type' => $m->getMorphClass(), 'payable_id' => $m->id, 'period_end' => today()->addMonth()]);
        app(RefundReconciler::class)->confirmed($refunded);
        $this->assertSame('associate', $m->fresh()->plan);
        $this->assertSame('active', $m->fresh()->status);
        $this->assertSame($older->period_end->toDateString(), $m->fresh()->current_period_end->toDateString());
    }

    public function test_spent_refunded_rewards_leave_auditable_debt_and_review_instead_of_free_value(): void
    {
        $u = $this->member();
        $p = $this->payment($u);
        app(TokenLedger::class)->credit($u, 'VT', 25, 'purchased', 'QA', $p);
        app(TokenLedger::class)->debit($u, 'VT', 10, 'used', 'QA usage');
        $result = app(RefundReconciler::class)->confirmed($p);
        $this->assertNotNull($result['review']);
        $this->assertSame(-10.0, app(TokenLedger::class)->balance($u, 'VT'));
        app(RefundReconciler::class)->confirmed($p);
        $this->assertSame(-10.0, app(TokenLedger::class)->balance($u, 'VT'));
    }

    public function test_historical_repair_command_is_scoped_and_never_calls_gateway(): void
    {
        $u = $this->member();
        $p = $this->payment($u);
        $other = $this->payment($u);
        app(TokenLedger::class)->credit($u, 'VT', 25, 'purchased', 'QA', $p);
        app(TokenLedger::class)->credit($u, 'VT', 25, 'purchased', 'QA', $other);
        $this->artisan('billing:reconcile-refunds')->assertExitCode(1);
        $this->artisan('billing:reconcile-refunds', ['--payment' => [$p->reference]])->assertSuccessful();
        $this->assertSame(25.0, app(TokenLedger::class)->balance($u, 'VT'));
        $this->assertNull($other->fresh()->refund_reconciled_at);
        Http::assertNothingSent();
    }

    public function test_gateway_failure_does_not_reverse_tokens_or_mark_refund_complete(): void
    {
        $u = $this->member();
        $p = $this->payment($u, ['status' => 'succeeded']);
        app(TokenLedger::class)->credit($u, 'VT', 25, 'purchased', 'QA', $p);
        Http::fake(['*' => Http::response(['messages' => ['resultCode' => 'Error']], 500)]);
        try {
            app(RefundReconciler::class)->refund($p);
            $this->fail('Gateway failure must be surfaced.');
        } catch (\App\Services\Billing\BillingException $e) {
        }
        $this->assertSame('succeeded', $p->fresh()->status);
        $this->assertSame(25.0, app(TokenLedger::class)->balance($u, 'VT'));
    }

    public function test_cancelled_membership_has_no_next_scheduled_charge_in_dashboard_data(): void
    {
        $u = $this->member();
        $u->memberships()->create(['plan' => 'premium', 'monthly_fee' => 25, 'status' => 'active', 'auto_renew' => false, 'cancelled_at' => now(), 'pending_plan' => 'free', 'current_period_end' => now()->addMonth()]);
        $member = app(MemberDashboardPresenter::class)->present($u->fresh())['member'];
        $this->assertSame(0, $member['nextChargeAmount']);
        $this->assertNull($member['nextChargeDate']);
        $this->assertNotNull($member['next']);
    }

    public function test_real_billing_service_renews_each_period_once_even_with_stale_replay(): void
    {
        $counter = 0;
        Http::fake(['*' => function () use (&$counter) {
            return Http::response(['transactionResponse' => ['responseCode' => '1', 'transId' => (string) ++$counter]]);
        }]);
        foreach (['monthly' => 1, 'quarterly' => 3, 'biannual' => 6, 'annual' => 12] as $freq => $months) {
            $u = $this->member();
            $u->paymentMethods()->create(['type' => 'card', 'brand' => 'Visa', 'last4' => '1111', 'exp_month' => 12, 'exp_year' => 2030, 'gateway_token' => '123', 'is_default' => true]);
            $m = $u->memberships()->create(['plan' => 'associate', 'monthly_fee' => 8.88, 'status' => 'active', 'billing_status' => 'active', 'auto_renew' => true, 'billing_frequency' => $freq, 'current_period_end' => now()->subMinute()]);
            $stale = Membership::findOrFail($m->id);
            app(MembershipService::class)->renew($m);
            app(MembershipService::class)->renew($stale);
            $this->assertSame(1, $m->payments()->where('status', 'succeeded')->count());
            $p = $m->payments()->firstOrFail();
            $this->assertEquals(round(8.88 * $months, 2), (float) $p->amount);
            $this->assertSame(4.0 * $months, app(TokenLedger::class)->balance($u, 'DT'));
            $this->assertSame('associate', $p->membership_plan);
            $this->assertTrue($m->fresh()->current_period_end->isFuture());
        }
        Http::assertSentCount(4);
    }

    public function test_policy_refund_reopens_balance_once_without_double_forfeiting_insurance_tokens(): void
    {
        $u = $this->member();
        $policy = $u->policies()->create(['type' => 'Auto', 'policy_number' => 'QA-REFUND', 'carrier' => 'QA', 'insured_name' => 'QA', 'annual_premium' => 1000, 'amount_due' => 0, 'starts_on' => today(), 'expires_on' => today()->addYear(), 'status' => 'cancelled']);
        $p = $this->payment($u, ['type' => Payment::TYPE_PREMIUM, 'amount' => 1000, 'payable_type' => $policy->getMorphClass(), 'payable_id' => $policy->id]);
        $ledger = app(TokenLedger::class);
        $ledger->credit($u, 'IT', 70, 'earned', 'QA', $p, extra: ['policy_id' => $policy->id, 'policy_year' => $policy->policyYear()]);
        $ledger->forfeitCurrentYear($policy);
        app(RefundReconciler::class)->confirmed($p);
        app(RefundReconciler::class)->confirmed($p);
        $this->assertEquals(1000, (float) $policy->fresh()->amount_due);
        $this->assertSame(0.0, $ledger->balance($u, 'IT'));
        $this->assertSame(0, $u->tokenTransactions()->where('kind', 'refunded')->count());
        Http::assertNothingSent();
    }
}
