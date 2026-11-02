<?php

namespace Tests\Feature;

use App\Admin\Resources\ArticleResource;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Appointment;
use App\Models\InsuranceCategory;
use App\Models\InsuranceProduct;
use App\Models\Partner;
use App\Models\Policy;
use App\Models\Resource as Article;
use App\Models\ResourceCategory;
use App\Models\User;
use App\Rules\PhoneNumber;
use App\Rules\VehicleIdentificationNumber;
use App\Support\PublicLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class QaRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        // Override even a cached local configuration before RefreshDatabase runs.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('services.klaviyo.key', null);
        $app['config']->set('mail.default', 'array');
        $app['config']->set('session.driver', 'array');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Notification::fake();
    }

    private function member(string $role = 'member'): User
    {
        return User::create(['first_name' => 'QA', 'last_name' => 'Fixture', 'email' => uniqid('qa-').'@example.invalid', 'password' => 'Test-only-password!26', 'role' => $role, 'status' => 'active']);
    }

    private function policy(User $user, string $type = 'Auto'): Policy
    {
        return $user->policies()->create(['type' => $type, 'policy_number' => uniqid('QA-'), 'carrier' => 'QA only', 'insured_name' => 'QA Fixture', 'annual_premium' => 1000, 'amount_due' => 0, 'starts_on' => today(), 'expires_on' => today()->addYear(), 'status' => 'active']);
    }

    private function paidMember(string $plan = 'premium'): User
    {
        $user = $this->member();
        $user->memberships()->create(['plan' => $plan, 'status' => 'active', 'monthly_fee' => $plan === 'premium' ? 25 : 8.88, 'started_at' => now()]);

        return $user;
    }

    private function vacationProperty(): Partner
    {
        return Partner::create(['name' => 'QA stay', 'slug' => uniqid('qa-'), 'category' => 'QA', 'description' => 'QA only', 'image_url' => 'https://example.invalid/qa.png', 'is_vacation' => true, 'nightly_rate' => 100, 'tokens_per_night' => 45]);
    }

    private function producerFixture(): \App\Models\Producer
    {
        $user = $this->paidMember('associate');
        $user->update(['role' => 'producer']);

        return $user->producer()->create(['producer_number' => uniqid('QA-P-'), 'status' => 'active', 'onboarded_on' => today(), 'qualification_started_on' => today()->subMonths(11)]);
    }

    public function test_otp_expiry_attempt_limit_purpose_resend_and_single_use(): void
    {
        config(['membership.otp.fixed_code' => '654321']);
        $codes = app(\App\Services\OneTimeCodeService::class);
        $email = 'qa-otp@example.invalid';
        $codes->issue($email, 'verify_email');
        $this->assertFalse($codes->verify($email, 'reset_password', '654321'));
        $this->assertTrue($codes->verify($email, 'verify_email', '654321'));
        $this->assertFalse($codes->verify($email, 'verify_email', '654321'));
        $codes->issue($email, 'verify_email');
        $this->travel(11)->minutes();
        $this->assertFalse($codes->verify($email, 'verify_email', '654321'));
        $this->travelBack();
        $codes->issue($email, 'verify_email');
        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse($codes->verify($email, 'verify_email', '000000'));
        }
        $this->assertFalse($codes->verify($email, 'verify_email', '654321'));
        config(['membership.otp.fixed_code' => '123456']);
        $codes->issue($email, 'verify_email');
        $this->assertFalse($codes->verify($email, 'verify_email', '654321'));
        $this->assertTrue($codes->verify($email, 'verify_email', '123456'));
    }

    public function test_public_free_signup_verification_duplicate_email_and_reset_lifecycle(): void
    {
        config(['membership.otp.fixed_code' => '654321']);
        $data = ['plan' => 'free', 'first' => 'QA', 'last' => 'Signup', 'email' => 'QA-SIGNUP@example.invalid', 'phone' => '2025550100', 'street' => 'QA ADDRESS', 'city' => 'QA', 'stt' => 'MI', 'zip' => '48307', 'pw' => 'Test-only-password!26', 'pw_confirmation' => 'Test-only-password!26', 'tos' => true, 'sms' => false];
        $this->postJson('/register', $data)->assertCreated()->assertJsonPath('next', 'verify');
        $this->postJson('/register/verify', ['code' => '000000'])->assertUnprocessable();
        $this->postJson('/register/verify', ['code' => '654321'])->assertOk()->assertJsonPath('next', 'done');
        $user = User::where('email', 'qa-signup@example.invalid')->firstOrFail();
        $this->assertSame('active', $user->status);
        $this->assertSame('active', $user->membership->status);
        $this->actingAs($user)->getJson('/dashboard/data')->assertOk();
        $this->post('/logout');
        $this->postJson('/register', $data)->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->postJson('/forgot-password', ['email' => $user->email])->assertOk()->assertExactJson(['sent' => true]);
        $this->postJson('/forgot-password/verify', ['email' => $user->email, 'code' => '654321'])->assertOk();
        $this->postJson('/reset-password', ['password' => 'Another-test-only!27', 'password_confirmation' => 'Another-test-only!27'])->assertOk();
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('Another-test-only!27', $user->fresh()->password));
        $this->postJson('/reset-password', ['password' => 'Another-test-only!28', 'password_confirmation' => 'Another-test-only!28'])->assertUnprocessable();
    }

    public function test_transfer_approval_rejection_and_stale_replay_do_not_duplicate_credit(): void
    {
        $sender = $this->paidMember();
        $recipient = $this->paidMember();
        $ledger = app(\App\Services\TokenLedger::class);
        $benefits = app(\App\Services\BenefitsService::class);
        $ledger->credit($sender, 'DT', 100, 'adjusted', 'QA fixture');
        $data = ['email' => $recipient->email, 'token_type' => 'DT', 'quantity' => 20, 'indemnity' => true];
        $this->actingAs($sender)->postJson('/dashboard/token-transfers', $data)->assertCreated();
        $transfer = \App\Models\TokenTransfer::firstOrFail();
        $stale = $transfer->fresh();
        $this->assertSame(80.0, $ledger->balance($sender, 'DT'));
        $benefits->approveTransfer($transfer);
        try {
            $benefits->approveTransfer($stale);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertSame(20.0, $ledger->balance($recipient, 'DT'));
        $this->postJson('/dashboard/token-transfers', array_replace($data, ['quantity' => 81]))->assertUnprocessable();
        $this->postJson('/dashboard/token-transfers', array_replace($data, ['email' => $sender->email]))->assertUnprocessable();
        $this->postJson('/dashboard/token-transfers', array_replace($data, ['token_type' => 'VT']))->assertUnprocessable();
        $this->postJson('/dashboard/token-transfers', $data)->assertCreated();
        $rejected = \App\Models\TokenTransfer::latest('id')->first();
        $staleRejected = $rejected->fresh();
        $benefits->rejectTransfer($rejected, 'QA test');
        try {
            $benefits->rejectTransfer($staleRejected, 'QA replay');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        }
        $this->assertSame(80.0, $ledger->balance($sender, 'DT'));
    }

    public function test_vacation_notice_validation_confirmation_and_member_cancellation_return_tokens_once(): void
    {
        $user = $this->paidMember();
        $property = $this->vacationProperty();
        $ledger = app(\App\Services\TokenLedger::class);
        $benefits = app(\App\Services\BenefitsService::class);
        $ledger->credit($user, 'DT', 100, 'adjusted', 'QA fixture');
        $data = ['property_id' => $property->id, 'check_in' => today()->addDays(90)->toDateString(), 'check_out' => today()->addDays(92)->toDateString(), 'guests' => 2];
        $this->actingAs($user)->postJson('/dashboard/vacations', array_replace($data, ['check_in' => 'not-a-date']))->assertUnprocessable();
        $this->postJson('/dashboard/vacations', array_replace($data, ['check_in' => today()->addDays(89)->toDateString()]))->assertUnprocessable();
        $this->postJson('/dashboard/vacations', array_replace($data, ['check_out' => $data['check_in']]))->assertUnprocessable();
        $this->postJson('/dashboard/vacations', array_replace($data, ['guests' => 999]))->assertUnprocessable();
        $this->postJson('/dashboard/vacations', $data)->assertCreated();
        $vacation = \App\Models\VacationRequest::firstOrFail();
        $this->assertSame(100.0, $ledger->balance($user, 'DT'));
        $benefits->confirmVacation($vacation, 'QA only');
        $this->assertSame(10.0, $ledger->balance($user, 'DT'));
        $this->postJson('/dashboard/vacations/'.$vacation->reference.'/cancel')->assertOk();
        $this->assertSame(100.0, $ledger->balance($user, 'DT'));
        $this->postJson('/dashboard/vacations/'.$vacation->reference.'/cancel')->assertUnprocessable();
        $this->assertSame(100.0, $ledger->balance($user, 'DT'));
    }

    public function test_qr_reissue_inactive_partner_permission_and_overdraft(): void
    {
        $user = $this->paidMember();
        $partner = $this->vacationProperty();
        $partner->update(['is_vacation' => false]);
        $benefits = app(\App\Services\BenefitsService::class);
        $ledger = app(\App\Services\TokenLedger::class);
        $ledger->credit($user, 'DT', 20, 'adjusted', 'QA fixture');
        $old = $benefits->issueQr($user);
        $qr = $benefits->issueQr($user);
        $this->assertSame('inactive', $old->fresh()->status);
        $admin = $this->member('admin');
        $this->actingAs($admin, 'admin')->postJson('/admin/qr-scan', ['code' => $old->code, 'partner_id' => $partner->id, 'amount' => 1])->assertUnprocessable();
        $this->postJson('/admin/qr-scan', ['code' => $qr->code, 'partner_id' => $partner->id, 'amount' => 21])->assertUnprocessable();
        $partner->update(['is_active' => false]);
        $this->postJson('/admin/qr-scan', ['code' => $qr->code, 'partner_id' => $partner->id, 'amount' => 5])->assertUnprocessable();
        $partner->update(['is_active' => true]);
        $this->post('/admin/qr-scan', ['code' => $qr->code, 'partner_id' => $partner->id, 'amount' => 5])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(15.0, $ledger->balance($user, 'DT'));
        $this->assertDatabaseCount('qr_redemptions', 1);
    }

    public function test_producer_portal_license_document_lead_isolation_and_discount(): void
    {
        Storage::fake('local');
        $producer = $this->producerFixture();
        $other = $this->producerFixture();
        $lead = \App\Models\Lead::create(['first_name' => 'QA', 'last_name' => 'Lead', 'email' => 'qa@example.invalid', 'phone' => '2025550100', 'source' => 'quote', 'insurance_type' => 'auto', 'zip' => '48307', 'producer_id' => $producer->id]);
        $this->actingAs($producer->user)->getJson('/producer/dashboard/data')->assertOk()->assertJsonPath('leads.0.id', $lead->reference);
        $this->postJson('/producer/leads/'.$lead->reference, ['status' => 'contacted', 'notes' => 'QA follow up'])->assertOk();
        $this->actingAs($other->user)->postJson('/producer/leads/'.$lead->reference, ['status' => 'won'])->assertNotFound();
        $this->getJson('/producer/dashboard/data')->assertOk()->assertJsonCount(0, 'leads');
        $this->actingAs($producer->user)->postJson('/producer/licenses', ['license_type' => 'Property & Casualty', 'state' => 'MI', 'license_number' => 'QA-NO-LICENSE', 'expires_on' => today()->addYear()->toDateString()])->assertCreated();
        $this->assertNull($producer->licenses()->first()->verified_at);
        $this->post('/producer/documents', ['file' => UploadedFile::fake()->create('qa.pdf', 1, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated();
        $document = $producer->documents()->first();
        $this->get('/producer/documents/'.$document->id)->assertOk();
        $this->actingAs($other->user)->get('/producer/documents/'.$document->id)->assertNotFound();
        $property = $this->vacationProperty();
        $this->actingAs($producer->user)->postJson('/producer/vacations', ['property_id' => $property->id, 'check_in' => today()->addDays(90)->toDateString(), 'check_out' => today()->addDays(92)->toDateString(), 'traveller' => 'Family'])->assertCreated();
        $this->assertDatabaseHas('vacation_requests', ['user_id' => $producer->user_id, 'discount_rate' => 0.1, 'payment_amount' => 180]);
        $this->postJson('/producer/profile', ['email' => $producer->user->email, 'phone' => '000'])->assertUnprocessable();
    }

    public function test_producer_commission_bonus_11_12_13_months_and_future_start(): void
    {
        $producer = $this->producerFixture();
        $service = app(\App\Services\ProducerService::class);
        $producer->licenses()->create(['license_type' => 'Property & Casualty', 'license_number' => 'QA', 'state' => 'MI', 'issued_on' => today()->subYear(), 'expires_on' => today()->addYears(2), 'verified_at' => now()]);
        $service->syncBonus($producer);
        $this->assertSame('locked', $producer->bonus_status);
        $producer->update(['qualification_started_on' => today()->subMonths(12)]);
        $service->syncBonus($producer->fresh());
        $this->assertSame('available', $producer->fresh()->bonus_status);
        $producer->update(['qualification_started_on' => today()->subMonths(13)]);
        $this->assertTrue($producer->fresh()->bonusEligibility()['months']);
        $producer->update(['qualification_started_on' => today()->addMonths(13)]);
        $this->assertFalse($producer->fresh()->bonusEligibility()['months']);
        $commission = $service->recordCommission($producer, ['reference' => 'QA-POLICY', 'insured' => 'QA', 'line' => 'Auto', 'written_on' => today()->toDateString(), 'premium' => 1000, 'commission_base' => 120]);
        $this->assertSame('90.00', $commission->amount);
    }

    public function test_private_policy_download_rejects_foreign_documents_and_sub_permissions(): void
    {
        Storage::fake('local');
        Storage::put('qa/policy.pdf', 'QA ONLY');
        $owner = $this->paidMember();
        $policy = $this->policy($owner);
        $foreign = $this->policy($this->member());
        $doc = $policy->documents()->create(['name' => 'QA', 'path' => 'qa/policy.pdf']);
        $this->actingAs($owner)->get('/dashboard/policies/'.$policy->id.'/documents/'.$doc->id)->assertOk();
        $this->get('/dashboard/policies/'.$foreign->id.'/documents/'.$doc->id)->assertNotFound();
        $sub = $this->member();
        $sub->update(['parent_id' => $owner->id, 'permissions' => []]);
        $this->actingAs($sub)->get('/dashboard/policies/'.$policy->id.'/documents/'.$doc->id)->assertForbidden();
        $sub->update(['permissions' => ['View policies']]);
        $this->get('/dashboard/policies/'.$policy->id.'/documents/'.$doc->id)->assertOk();
    }

    public function test_producer_approval_upgrades_free_fixture_and_is_not_repeatable(): void
    {
        $user = $this->member();
        $user->memberships()->create(['plan' => 'free', 'status' => 'active', 'monthly_fee' => 0, 'started_at' => now()]);
        $application = \App\Models\ProducerApplication::create(['first_name' => 'QA', 'last_name' => 'Applicant', 'email' => $user->email, 'phone' => '2025550100', 'street' => 'QA ADDRESS', 'city' => 'QA', 'state' => 'MI', 'zip' => '48307', 'occupation' => 'QA', 'experience' => 'None yet', 'years_experience' => '0', 'license_status' => 'Not licensed', 'reason' => 'QA only', 'terms_accepted_at' => now()]);
        $stale = $application->fresh();
        $service = app(\App\Services\ProducerService::class);
        $producer = $service->approve($application);
        $this->assertSame('producer', $user->fresh()->role);
        $this->assertSame('associate', $user->fresh()->membership->plan);
        $this->assertSame('pending', $user->fresh()->membership->status);
        $this->assertNull($producer->qualification_started_on);
        $this->actingAs($user->fresh())->getJson('/producer/dashboard/data')->assertOk();
        try {
            $service->approve($stale);
            $this->fail('Repeated approval must reject');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }
        $this->assertDatabaseCount('producers', 1);
        $service->recordCommission($producer, ['reference' => 'QA-FIRST', 'insured' => 'QA', 'line' => 'Auto', 'written_on' => today()->toDateString(), 'premium' => 1000, 'commission_base' => 100]);
        $this->assertSame(today()->toDateString(), $producer->fresh()->qualification_started_on->toDateString());
    }

    public function test_suspended_sessions_and_ineligible_bonus_redemptions_are_denied(): void
    {
        $user = $this->paidMember();
        $user->update(['status' => 'suspended']);
        $this->actingAs($user)->getJson('/dashboard/data')->assertForbidden();
        $this->postJson('/dashboard/vacations', [])->assertForbidden();
        $producer = $this->producerFixture();
        $producer->licenses()->create(['license_type' => 'Property & Casualty', 'license_number' => 'QA', 'state' => 'MI', 'issued_on' => today()->subYear(), 'expires_on' => today()->addYear(), 'verified_at' => now()]);
        $producer->update(['qualification_started_on' => today()->subMonths(12)]);
        $service = app(\App\Services\ProducerService::class);
        $service->syncBonus($producer);
        $this->assertSame('available', $producer->bonus_status);
        $producer->user->membership->update(['status' => 'cancelled']);
        $this->actingAs($producer->user)->postJson('/producer/bonus/redeem')->assertUnprocessable();
        $producer->user->membership->update(['status' => 'active']);
        $this->postJson('/producer/bonus/redeem')->assertOk();
        $this->postJson('/producer/bonus/redeem')->assertUnprocessable();
    }

    public function test_export_cells_are_formula_safe_and_negative_numbers_remain_numeric(): void
    {
        $this->assertSame(["'=1+1", "' +cmd", "'@SUM(A1)", '-100', -5, 'QA'], \App\Support\CsvRow::safe(['=1+1', ' +cmd', '@SUM(A1)', '-100', -5, 'QA']));
        $user = $this->member();
        $user->update(['first_name' => '=1+1']);
        $response = $this->actingAs($this->member('admin'), 'admin')->get('/admin/reports/members.csv')->assertOk();
        $this->assertStringContainsString("'=1+1", $response->streamedContent());
        $this->actingAs($user, 'admin')->get('/admin/reports/members.csv')->assertForbidden();
    }

    public function test_policy_cancellation_forfeits_only_current_year_insurance_tokens_once(): void
    {
        $user = $this->paidMember();
        $policy = $this->policy($user);
        $ledger = app(\App\Services\TokenLedger::class);
        $ledger->credit($user, 'IT', 50, 'earned', 'QA completed year', extra: ['policy_id' => $policy->id, 'policy_year' => '2025–26']);
        $ledger->credit($user, 'IT', 70, 'earned', 'QA current year', extra: ['policy_id' => $policy->id, 'policy_year' => $policy->policyYear()]);
        $this->assertTrue($ledger->hasInsuranceTokens($policy));
        $ledger->forfeitCurrentYear($policy);
        $ledger->forfeitCurrentYear($policy);
        $this->assertSame(50.0, $ledger->balance($user, 'IT'));
        $this->assertSame(1, $user->tokenTransactions()->where('kind', 'forfeited')->count());
    }

    public function test_claim_messages_upload_validation_and_owner_isolation(): void
    {
        Storage::fake('local');
        $user = $this->paidMember();
        $other = $this->paidMember();
        $claim = \App\Models\Claim::create(['user_id' => $user->id, 'first_name' => 'QA', 'last_name' => 'Fixture', 'email' => $user->email, 'phone' => '2025550100', 'preferred_contact' => 'Email', 'policy_number' => 'QA-ONLY', 'insurance_type' => 'Auto', 'claim_type' => 'Glass', 'incident_date' => today(), 'location' => 'QA ADDRESS', 'description' => 'QA only']);
        $url = '/dashboard/claims/'.$claim->reference;
        $this->actingAs($other)->postJson($url.'/messages', ['body' => 'QA'])->assertNotFound();
        $this->post($url.'/documents', ['file' => UploadedFile::fake()->create('qa.pdf', 1, 'application/pdf')], ['Accept' => 'application/json'])->assertNotFound();
        $this->actingAs($user)->postJson($url.'/messages', ['body' => 'QA update'])->assertCreated();
        $this->post($url.'/documents', ['file' => UploadedFile::fake()->create('qa.exe', 1, 'application/octet-stream')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post($url.'/documents', ['file' => UploadedFile::fake()->create('qa.pdf', 11000, 'application/pdf')], ['Accept' => 'application/json'])->assertUnprocessable();
        $this->post($url.'/documents', ['file' => UploadedFile::fake()->create('qa.pdf', 1, 'application/pdf')], ['Accept' => 'application/json'])->assertCreated();
        $this->assertCount(1, $claim->attachments);
        $this->assertSame(2, $claim->messages()->count());
    }

    public function test_notification_owner_read_and_admin_settings_are_editable(): void
    {
        $user = $this->paidMember();
        $other = $this->paidMember();
        $notice = $user->notifications()->create(['id' => (string) \Illuminate\Support\Str::uuid(), 'type' => 'QA', 'data' => ['title' => 'QA only']]);
        $this->actingAs($other)->postJson('/dashboard/notifications/'.$notice->id.'/read')->assertNotFound();
        $this->actingAs($user)->postJson('/dashboard/notifications/'.$notice->id.'/read')->assertOk();
        $this->assertNotNull($notice->fresh()->read_at);
        $this->postJson('/dashboard/notifications/read-all')->assertOk();
        $admin = $this->member('admin');
        $data = [];
        foreach (\App\Models\Setting::EDITABLE as $key => $label) {
            $value = config('cospark.'.$key);
            $data[str_replace('.', '__', $key)] = is_array($value) ? implode("\n", $value) : $value;
        }
        $data['legal__privacy'] = 'QA fixture policy text';
        $this->actingAs($admin, 'admin')->get('/admin/settings')->assertOk();
        $this->post('/admin/settings', $data)->assertSessionHasNoErrors()->assertRedirect();
        \App\Models\Setting::applyToConfig();
        $this->get('/legal')->assertOk()->assertSee('QA fixture policy text');
    }

    public function test_vacation_stale_confirmation_and_cancel_cannot_double_post_tokens(): void
    {
        $user = $this->paidMember();
        $property = $this->vacationProperty();
        $ledger = app(\App\Services\TokenLedger::class);
        $benefits = app(\App\Services\BenefitsService::class);
        $ledger->credit($user, 'DT', 100, 'adjusted', 'QA');
        $vacation = $user->vacationRequests()->create(['partner_id' => $property->id, 'check_in' => today()->addDays(90), 'check_out' => today()->addDays(91), 'guests' => 1, 'tokens_applied' => 50, 'payment_amount' => 50]);
        $stale = $vacation->fresh();
        $benefits->confirmVacation($vacation, 'QA');
        try {
            $benefits->confirmVacation($stale, 'QA');
            $this->fail('Stale confirmation must reject');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        }
        $this->assertSame(50.0, $ledger->balance($user, 'DT'));
        $stale = $vacation->fresh();
        $benefits->setVacationStatus($vacation, 'cancelled', 'QA');
        try {
            $benefits->setVacationStatus($stale, 'cancelled', 'QA');
            $this->fail('Stale cancellation must reject');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
        }
        $this->assertSame(100.0, $ledger->balance($user, 'DT'));
        $another = $user->vacationRequests()->create(['partner_id' => $property->id, 'check_in' => today()->addDays(95), 'check_out' => today()->addDays(96), 'guests' => 1, 'tokens_applied' => 100, 'payment_amount' => 0]);
        $ledger->debit($user, 'DT', 90, 'used', 'QA competing use');
        try {
            $benefits->confirmVacation($another, 'QA');
            $this->fail('Insufficient balance must reject');
        } catch (\Illuminate\Validation\ValidationException $e) {
        }
        $this->assertSame('pending', $another->fresh()->status);
        $this->assertSame(10.0, $ledger->balance($user, 'DT'));
    }

    public function test_quote_draft_requires_matching_email_and_expires(): void
    {
        $saved = $this->postJson('/quote/save', ['email' => 'qa@example.invalid', 'state' => ['type' => 'Auto', 'zip' => '48307'], 'step' => 2])->assertCreated();
        $code = $saved->json('code');
        $this->post('/quote/retrieve', ['email' => 'other@example.invalid', 'code' => $code])->assertSessionHasErrors('code');
        $this->post('/quote/retrieve', ['email' => 'qa@example.invalid', 'code' => $code])->assertSessionHasNoErrors()->assertRedirect(route('quote', ['resume' => $code]));
        $this->travel(31)->days();
        $this->post('/quote/retrieve', ['email' => 'qa@example.invalid', 'code' => $code])->assertSessionHasErrors('code');
        $this->travelBack();
    }

    public function test_asset_urls_include_content_version_for_deployed_fixes(): void
    {
        $hash = substr(hash_file('sha256', public_path('assets/js/main.js')), 0, 12);
        $this->get('/contact')->assertOk()->assertSee('assets/js/main.js?v='.config('app.asset_version').'.'.$hash, false);
        $this->assertSame(config('app.asset_version').'.'.substr(hash_file('sha256', public_path('assets/js/quote.js')), 0, 12), \App\Support\AssetVersion::for('assets/js/quote.js'));
    }

    public function test_plan_token_and_dues_formulas_for_all_non_payment_periods(): void
    {
        foreach (['associate' => [4, 8.88, 0.05], 'premium' => [12, 25, 0.07]] as $plan => [$tokens,$fee,$rate]) {
            $user = $this->paidMember($plan);
            foreach (['monthly' => 1, 'quarterly' => 3, 'biannual' => 6, 'annual' => 12] as $frequency => $months) {
                $membership = $user->membership;
                $membership->update(['billing_frequency' => $frequency]);
                $this->assertSame($tokens * $months, $membership->periodDuesTokens());
                $this->assertSame(round($fee * $months, 2), $membership->periodAmount());
                $this->assertSame($rate, $membership->planConfig()['insurance_token_rate']);
            }
        }
    }

    public function test_sub_account_benefits_require_current_parent_membership_and_permission(): void
    {
        $owner = $this->paidMember();
        $sub = $this->member();
        $property = $this->vacationProperty();
        $sub->update(['parent_id' => $owner->id, 'permissions' => ['Vacations']]);
        $data = ['property_id' => $property->id, 'check_in' => today()->addDays(90)->toDateString(), 'check_out' => today()->addDays(91)->toDateString(), 'guests' => 1];
        $this->actingAs($sub)->postJson('/dashboard/vacations', $data)->assertCreated();
        $owner->membership->update(['status' => 'expired']);
        $this->actingAs($sub->fresh())->postJson('/dashboard/vacations', $data)->assertForbidden();
        $owner->membership->update(['status' => 'active']);
        $sub->update(['permissions' => []]);
        $this->actingAs($sub->fresh())->postJson('/dashboard/vacations', $data)->assertForbidden();
    }

    public function test_profile_validation_avatar_upload_and_replacement(): void
    {
        Storage::fake('public');
        $user = $this->member();
        $data = ['first' => 'QA', 'last' => 'Updated', 'email' => $user->email, 'phone' => '2025550100', 'city' => 'QA Updated', 'st' => 'MI', 'zip' => '48307', 'sms' => false];
        $this->actingAs($user)->postJson('/dashboard/profile', array_replace($data, ['phone' => '000', 'zip' => 'abc']))->assertUnprocessable()->assertJsonValidationErrors(['phone', 'zip']);
        $this->postJson('/dashboard/profile', $data)->assertOk();
        $this->assertSame('QA Updated', $user->fresh()->city);
        $this->post('/dashboard/profile/avatar', ['avatar' => UploadedFile::fake()->create('qa.exe', 1, 'application/octet-stream')], ['Accept' => 'application/json'])->assertUnprocessable();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aH1sAAAAASUVORK5CYII=');
        $this->post('/dashboard/profile/avatar', ['avatar' => UploadedFile::fake()->createWithContent('qa.png', $png)], ['Accept' => 'application/json'])->assertOk();
        $old = $user->fresh()->avatar_path;
        Storage::disk('public')->assertExists($old);
        $this->post('/dashboard/profile/avatar', ['avatar' => UploadedFile::fake()->createWithContent('qa-new.png', $png)], ['Accept' => 'application/json'])->assertOk();
        Storage::disk('public')->assertMissing($old);
        Storage::disk('public')->assertExists($user->fresh()->avatar_path);
    }

    public function test_klaviyo_event_contract_normalizes_phone_and_preserves_notification_identity(): void
    {
        config(['services.klaviyo.key' => 'QA-ONLY-NOT-A-REAL-KEY']);
        Http::fake(['a.klaviyo.com/api/events/' => Http::response([], 202)]);
        app(\App\Services\Klaviyo::class)->event('COSPARK Email', 'qa@example.invalid', '(202) 555-0100', ['subject' => 'QA only'], 'qa-unique-event');
        Http::assertSent(function ($request) {
            $data = json_decode($request->body(), true)['data'];

            return $request->method() === 'POST' && $request->hasHeader('revision', '2025-10-15') && $request->hasHeader('Authorization', 'Klaviyo-API-Key QA-ONLY-NOT-A-REAL-KEY') && $data['type'] === 'event' && $data['attributes']['metric']['data']['attributes']['name'] === 'COSPARK Email' && $data['attributes']['profile']['data']['attributes']['phone_number'] === '+12025550100' && $data['attributes']['unique_id'] === 'qa-unique-event';
        });
        Http::assertSentCount(1);
        $this->assertSame('+442079460958', \App\Services\Klaviyo::e164('+44 20 7946 0958'));
        $this->assertNull(\App\Services\Klaviyo::e164('000'));
    }

    public function test_all_email_goes_over_smtp_even_with_a_klaviyo_key(): void
    {
        config(['services.klaviyo.key' => 'QA-ONLY-NOT-A-REAL-KEY']);
        Http::fake();
        // Boot the app's provider with the key set against a real (not faked) channel manager
        $manager = new \Illuminate\Notifications\ChannelManager(app());
        Notification::swap($manager);
        (new \App\Providers\AppServiceProvider(app()))->boot();
        $this->assertInstanceOf(\Illuminate\Notifications\Channels\MailChannel::class, $manager->driver('mail'));

        $to = (new \Illuminate\Notifications\AnonymousNotifiable)->route('mail', 'qa@example.invalid');
        $manager->sendNow($to, new \App\Notifications\StatusUpdate('QA subject', 'QA', ['Reference QA-654321']));
        $manager->sendNow($to, new \App\Notifications\OneTimeCodeNotification('654321', 'verify_email', 10));

        Http::assertNothingSent();
        $sent = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(2, $sent);
        $this->assertStringContainsString('QA-654321', $sent[0]->getOriginalMessage()->getHtmlBody());
        $this->assertStringContainsString('654321', $sent[1]->getOriginalMessage()->getHtmlBody());
    }

    public function test_klaviyo_sms_channel_honors_local_opt_out_and_global_disable(): void
    {
        $user = $this->member();
        $user->phone = '2025550100';
        config(['services.klaviyo.key' => 'QA-ONLY-NOT-A-REAL-KEY', 'cospark.sms_enabled' => true]);
        Http::fake(['a.klaviyo.com/api/events/' => Http::response([], 202)]);
        $channel = new \App\Notifications\Channels\SmsChannel(new \App\Services\Sms\KlaviyoSmsSender(app(\App\Services\Klaviyo::class)));
        $notice = new \App\Notifications\MemberAlert('Membership', 'QA only', 'QA notification', 'qr', true);
        $channel->send($user, $notice);
        Http::assertNothingSent();
        $user->sms_opt_in = true;
        config(['cospark.sms_enabled' => false]);
        $channel->send($user, $notice);
        Http::assertNothingSent();
        config(['cospark.sms_enabled' => true]);
        $channel->send($user, $notice);
        Http::assertSent(fn ($request) => json_decode($request->body(), true)['data']['attributes']['metric']['data']['attributes']['name'] === 'COSPARK SMS');
        Http::assertSentCount(1);
    }

    public function test_klaviyo_subscription_jobs_and_api_failure_are_observable(): void
    {
        $user = $this->member();
        $user->phone = '2025550100';
        config(['services.klaviyo.key' => 'QA-ONLY-NOT-A-REAL-KEY', 'services.klaviyo.sms_list' => 'QA-LIST']);
        Http::fake(['a.klaviyo.com/api/*' => Http::response([], 202)]);
        $user->sms_opt_in = true;
        app(\App\Services\Klaviyo::class)->syncSmsConsent($user);
        $user->sms_opt_in = false;
        app(\App\Services\Klaviyo::class)->syncSmsConsent($user);
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/profile-subscription-bulk-create-jobs/'));
        Http::assertSent(fn ($request) => str_ends_with($request->url(), '/profile-subscription-bulk-delete-jobs/'));
        Http::assertSentCount(2);
    }

    public function test_klaviyo_api_failure_is_not_reported_as_success(): void
    {
        config(['services.klaviyo.key' => 'QA-ONLY-NOT-A-REAL-KEY']);
        Http::fake(['a.klaviyo.com/api/*' => Http::response(['errors' => [['detail' => 'QA unauthorized']]], 401)]);
        $this->expectException(\Illuminate\Http\Client\RequestException::class);
        app(\App\Services\Klaviyo::class)->event('COSPARK Email', 'qa@example.invalid', null, ['subject' => 'QA failure']);
    }

    public function test_phone_validation_rejects_bad_supplied_numbers_but_keeps_contact_optional(): void
    {
        foreach (['000', '0000000000', '123', '+1 202 abc 0100', str_repeat('1', 16)] as $phone) {
            $this->assertTrue(Validator::make(['phone' => $phone], ['phone' => [new PhoneNumber]])->fails(), $phone);
            $this->postJson('/contact', ['first_name' => 'QA', 'last_name' => 'Test', 'email' => 'qa@example.invalid', 'phone' => $phone, 'message' => 'QA only'])->assertUnprocessable()->assertJsonValidationErrors('phone');
        }
        foreach (['2025550100', '+1 (202) 555-0100', '+44 20 7946 0958'] as $phone) {
            $this->assertFalse(Validator::make(['phone' => $phone], ['phone' => [new PhoneNumber]])->fails(), $phone);
        }
        $this->postJson('/contact', ['first_name' => 'QA', 'last_name' => 'Test', 'email' => 'qa@example.invalid', 'message' => 'QA only'])->assertCreated();
        $this->assertDatabaseCount('contact_messages', 1);
    }

    public function test_vin_validation_uses_each_vehicle_year(): void
    {
        $rules = ['vehicles.*.vin' => ['nullable', new VehicleIdentificationNumber]];
        $data = ['vehicles' => [['year' => 2020, 'vin' => '1HGCM82633A004352'], ['year' => 1970, 'vin' => 'CLASSIC123']]];
        $this->assertFalse(Validator::make($data, $rules)->fails());
        $data['vehicles'][0]['vin'] = 'INVALID';
        $this->assertTrue(Validator::make($data, $rules)->fails());
        $data['vehicles'][0]['vin'] = '1HGCM82633A00435I';
        $this->assertTrue(Validator::make($data, $rules)->fails());
        $data['vehicles'][0]['vin'] = '';
        $this->assertFalse(Validator::make($data, $rules)->fails());
    }

    public function test_all_eight_quote_flows_submit_and_persist_the_correct_product(): void
    {
        $data = [
            'zip' => '48307', 'first' => 'QA', 'last' => 'Fixture', 'email' => 'qa@example.invalid', 'phone' => '2025550100', 'pref' => 'Email', 'street' => 'QA ADDRESS', 'city' => 'QA City', 'st' => 'MI',
            'drivers' => [['name' => 'QA Driver', 'dob' => '1990-01-01', 'dl' => 'QA-ONLY', 'dlst' => 'MI', 'acc' => '0', 'viol' => '0', 'claims' => '0']],
            'vehicles' => [['year' => 2020, 'make' => 'QA', 'model' => 'TEST', 'vin' => '1HGCM82633A004352', 'own' => 'Owned', 'use' => 'Pleasure', 'miles' => 'Under 5,000']],
            'insured' => 'No', 'paddr' => 'QA ADDRESS', 'occ' => 'Owner occupied', 'built' => 2000, 'sqft' => 1500, 'beds' => '3', 'baths' => '2', 'constr' => 'Frame', 'roof' => 'Metal', 'roofage' => 5, 'value' => '$150k–$300k',
            'raddr' => 'QA ADDRESS', 'dwell' => 'Apartment', 'occupants' => '1', 'length' => 'New move-in', 'ppv' => '$25k', 'liab' => '$100k', 'caddr' => 'QA ADDRESS', 'unit' => '1', 'addv' => '$10k',
            'aname' => 'QA Applicant', 'gender' => 'Prefer not to say', 'adob' => '1990-01-01', 'amount' => '$100,000', 'ptype' => 'Term', 'dur' => '10 years', 'occupation' => 'QA', 'hazard' => 'No', 'tobacco' => 'No', 'health' => 'Prefer to discuss', 'existing' => 'No',
            'legal' => 'QA COMPANY', 'entity' => 'LLC', 'industry' => 'QA', 'baddr' => 'QA ADDRESS', 'years' => 'New / startup', 'revenue' => 'Under $250k', 'emp' => 'Just me', 'activities' => 'QA only', 'locations' => '1', 'property' => 'Leased', 'cov' => ['General liability'],
        ];
        foreach (config('cospark.quote_types') as $key => $label) {
            $payload = array_replace($data, ['type' => $label]);
            if ($key === 'business') {
                $payload['vehicles'] = 'No';
            }
            $this->postJson('/quote', ['payload' => json_encode($payload)])->assertCreated()->assertJsonStructure(['reference']);
            $this->assertDatabaseHas('leads', ['insurance_type' => $key, 'email' => 'qa@example.invalid']);
        }
        $this->assertDatabaseCount('leads', 8);
        $request = StoreQuoteRequest::create('/', 'POST', array_replace($data, ['type' => config('cospark.quote_types.auto'), 'drivers' => [array_replace($data['drivers'][0], ['dob' => today()->addDay()->toDateString()])]]));
        $this->assertTrue(Validator::make($request->all(), $request->rules())->fails());
    }

    public function test_claim_prefill_uses_selected_owned_policy_and_rejects_other_members(): void
    {
        $user = $this->member();
        $auto = $this->policy($user);
        $home = $this->policy($user, 'Home');
        $foreign = $this->policy($this->member());
        $this->actingAs($user)->get('/claims/file?type=auto&policy='.$auto->id)->assertOk()->assertViewHas('prefill', fn ($p) => $p['policy'] === $auto->policy_number && $p['itype'] === 'Auto');
        $this->get('/claims/file?policy='.$home->id)->assertOk()->assertViewHas('prefill', fn ($p) => $p['policy'] === $home->policy_number);
        $this->get('/claims/file?policy='.$foreign->id)->assertNotFound();
        $this->get('/claims/file')->assertOk()->assertViewHas('prefill', fn ($p) => $p['policy'] === '');
        $this->get('/claims/file?type=auto')->assertOk()->assertViewHas('prefill', fn ($p) => $p['policy'] === $auto->policy_number);
        $this->policy($user);
        $this->get('/claims/file?type=auto')->assertOk()->assertViewHas('prefill', fn ($p) => $p['policy'] === '');
        $health = $this->policy($user, 'Health');
        $this->get('/claims/file?type=health&policy='.$health->id)->assertOk()->assertViewHas('prefill', fn ($p) => $p['policy'] === $health->policy_number && $p['itype'] === 'Other');
        $this->getJson('/claims/file?type[]=auto')->assertUnprocessable();
        $this->getJson('/claims/file?policy=bad')->assertUnprocessable();
    }

    public function test_all_seven_claim_categories_persist_and_future_incidents_are_rejected(): void
    {
        $base = ['first' => 'QA', 'last' => 'Fixture', 'email' => 'qa@example.invalid', 'phone' => '2025550100', 'pref' => 'Email', 'policy' => 'QA-NO-COVERAGE', 'date' => today()->toDateString(), 'loc' => 'QA ADDRESS', 'desc' => 'QA only', 'vehicle' => 'QA car', 'police' => 'No', 'other' => 'No', 'drivable' => 'Yes', 'injury' => 'No', 'rooms' => ['Kitchen'], 'habitable' => 'Yes', 'closed' => 'No', 'injured' => 'No', 'insured' => 'QA', 'relation' => 'QA'];
        foreach (['Auto' => 'Glass', 'Home' => 'Water damage', 'Renters' => 'Theft', 'Condo' => 'Fire', 'Commercial' => 'Liability', 'Life' => 'Death benefit', 'Other' => 'Other loss'] as $type => $claim) {
            $policy = $this->policy($holder ??= $this->member(), ['Commercial' => 'Business', 'Other' => 'Health'][$type] ?? $type);
            $this->postJson('/claims', ['payload' => json_encode(['policy' => $policy->policy_number] + $base + ['itype' => $type, 'ctype' => $claim])])->assertCreated()->assertJsonStructure(['reference']);
            $this->assertDatabaseHas('claims', ['insurance_type' => $type, 'claim_type' => $claim]);
        }
        $this->postJson('/claims', ['payload' => json_encode(array_replace($base, ['itype' => 'Auto', 'ctype' => 'Glass', 'date' => today()->addDay()->toDateString()]))])->assertUnprocessable()->assertJsonValidationErrors('date');
        $this->assertDatabaseCount('claims', 7);
    }

    public function test_producer_application_requires_real_resume_upload_and_checks_dates(): void
    {
        Storage::fake('local');
        $data = ['first' => 'QA', 'last' => 'Fixture', 'email' => 'qa@example.invalid', 'phone' => '2025550100', 'street' => 'QA ADDRESS', 'city' => 'QA', 'st' => 'MI', 'zip' => '48307', 'occ' => 'QA', 'exp' => 'None yet', 'years' => '0', 'lic' => 'Not licensed', 'why' => 'QA only', 'resume' => ['qa.pdf']];
        $this->postJson('/producer/apply', ['payload' => json_encode($data)])->assertUnprocessable()->assertJsonValidationErrors('files.resume');
        $this->post('/producer/apply', ['payload' => json_encode($data), 'files' => ['resume' => [UploadedFile::fake()->create('qa.pdf', 1, 'application/pdf')]]], ['Accept' => 'application/json'])->assertCreated();
        $this->assertDatabaseCount('producer_applications', 1);
        $this->postJson('/producer/apply', ['payload' => json_encode($data + ['issue' => '2030-01-01', 'exp_d' => '2020-01-01'])])->assertUnprocessable()->assertJsonValidationErrors(['issue', 'exp_d']);
    }

    public function test_cms_draft_publish_edit_unpublish_and_duplicate_slug(): void
    {
        $category = ResourceCategory::create(['name' => 'QA', 'slug' => 'qa']);
        $data = ['title' => 'QA only', 'slug' => 'qa-only', 'resource_category_id' => $category->id, 'summary' => 'QA only', 'image_url' => 'https://example.invalid/qa.png', 'body' => '<p>QA fixture only.</p>', 'read_minutes' => 1, 'sort_order' => 999, 'is_published' => false];
        $this->actingAs($this->member('admin'), 'admin')->post('/admin/article', $data)->assertSessionHasNoErrors()->assertRedirect();
        $article = Article::where('slug', 'qa-only')->firstOrFail();
        $this->get('/resources/qa-only')->assertNotFound();
        $this->post('/admin/article', $data)->assertSessionHasErrors('slug');
        $this->put('/admin/article/'.$article->id, array_replace($data, ['is_published' => true]))->assertSessionHasNoErrors()->assertRedirect();
        $this->get('/resources/qa-only')->assertOk()->assertSee('QA fixture only.');
        $this->put('/admin/article/'.$article->id, array_replace($data, ['title' => 'QA updated', 'is_published' => false]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertDatabaseHas('resources', ['id' => $article->id, 'title' => 'QA updated', 'is_published' => false]);
        $this->get('/resources/qa-only')->assertNotFound();
    }

    public function test_sub_accounts_only_prefill_parent_policies_with_permission(): void
    {
        $parent = $this->member();
        $policy = $this->policy($parent);
        $sub = $this->member();
        $sub->update(['parent_id' => $parent->id, 'permissions' => []]);
        $this->actingAs($sub)->get('/claims/file?policy='.$policy->id)->assertNotFound();
        $sub->update(['permissions' => ['View policies']]);
        $this->get('/claims/file?policy='.$policy->id)->assertOk()->assertViewHas('prefill', fn ($p) => $p['policy'] === $policy->policy_number);
    }

    public function test_admin_cannot_create_a_producer_without_onboarding(): void
    {
        $this->actingAs($this->member('admin'), 'admin')->post('/admin/user', ['first_name' => 'QA', 'last_name' => 'Producer', 'email' => 'newproducer@example.invalid', 'role' => 'producer', 'password' => 'Test-only-password!26'])->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'newproducer@example.invalid']);
    }

    public function test_unconfigured_external_links_are_not_clickable(): void
    {
        foreach ([null, '', '#', 'javascript:alert(1)', '/local', 'ftp://example.com'] as $url) {
            $this->assertNull(PublicLink::external($url));
        }
        $this->assertSame('https://example.com/partner', PublicLink::external('https://example.com/partner'));
        config(['cospark.social.facebook' => '#']);
        $this->get('/contact')->assertOk()->assertDontSee('aria-label="Facebook"', false);
        Partner::create(['name' => 'QA partner', 'slug' => 'qa-partner', 'category' => 'QA', 'description' => 'QA only', 'image_url' => 'https://example.invalid/qa.png', 'website_url' => '#']);
        $this->get('/ecosystem')->assertOk()->assertSee('Partner link coming soon');
    }

    public function test_pet_and_device_handoffs_preserve_product_interest(): void
    {
        $category = InsuranceCategory::create(['name' => 'QA', 'slug' => 'qa', 'quote_type' => 'home', 'coverage' => [], 'options' => [], 'benefits' => []]);
        foreach (['pet', 'device-protection'] as $slug) {
            $product = InsuranceProduct::create(['insurance_category_id' => $category->id, 'name' => 'QA '.$slug, 'slug' => $slug, 'quote_type' => 'home', 'image_url' => 'https://example.invalid/qa.png', 'tagline' => 'QA only', 'intro' => 'QA only', 'is_active' => true]);
            $this->get('/insurance/'.$slug)->assertOk()->assertViewHas('quoteUrl', route('contact', ['product' => $slug]));
            $this->get('/contact?product='.$slug)->assertOk()->assertSee('Please contact me about a '.$product->short_name.' insurance quote.');
        }
    }

    public function test_empty_article_cannot_be_published(): void
    {
        request()->merge(['is_published' => true]);
        foreach (['', '<p>&nbsp;</p>'] as $body) {
            $errors = Validator::make(['body' => $body], (new ArticleResource)->rules(null))->errors();
            $this->assertTrue($errors->has('body'));
        }
    }

    public function test_admin_upcoming_and_plan_counts_use_current_active_members(): void
    {
        $admin = $this->member('admin');
        $member = $this->member();
        $member->memberships()->create(['plan' => 'premium', 'status' => 'active', 'monthly_fee' => 30, 'started_at' => now()]);
        $member->memberships()->create(['plan' => 'free', 'status' => 'active', 'monthly_fee' => 0, 'started_at' => now()]);
        $producer = $this->member('producer');
        $producer->memberships()->create(['plan' => 'premium', 'status' => 'active', 'monthly_fee' => 30, 'started_at' => now()]);
        foreach (['pending', 'confirmed', 'declined', 'cancelled'] as $status) {
            Appointment::create(['type' => 'phone', 'preferred_date' => today()->addDays(5), 'preferred_time' => '3:30 PM', 'name' => 'QA', 'email' => 'qa@example.invalid', 'phone' => '2025550100', 'reason' => 'QA', 'status' => $status]);
        }
        $response = $this->actingAs($admin, 'admin')->get('/admin');
        $response->assertOk()->assertViewHas('page', function ($page) {
            $props = $page['props'];
            $this->assertCount(2, $props['upcoming']);
            $kpi = collect($props['kpis'])->firstWhere('label', 'Free · Associate · Premium');
            $this->assertSame('1 · 0 · 0', $kpi['value']);

            return true;
        });
    }

    public function test_member_quote_requests_show_on_my_policies_until_won(): void
    {
        $user = $this->member();
        $lead = fn (array $extra) => \App\Models\Lead::create(['first_name' => 'QA', 'last_name' => 'Lead', 'phone' => '2025550100', 'source' => 'quote', 'insurance_type' => 'auto', 'zip' => '48307', ...$extra]);
        $mine = $lead(['email' => 'other@example.invalid', 'user_id' => $user->id]);
        $byEmail = $lead(['email' => $user->email]);
        $lead(['email' => $user->email, 'user_id' => $user->id, 'status' => 'won']);
        $lead(['email' => 'stranger@example.invalid']);

        $quotes = $this->actingAs($user)->getJson('/dashboard/data')->assertOk()->json('quotes');
        $this->assertEqualsCanonicalizing([$mine->reference, $byEmail->reference], array_column($quotes, 'ref'));
        $this->assertSame('Quote requested', $quotes[0]['status']);
    }

    public function test_membership_cards_mark_the_active_plan_and_lock_others_for_paid_members(): void
    {
        $this->get('/membership')->assertOk()->assertSee('/register?plan=premium', false)->assertDontSee('>ACTIVE<', false);

        $premium = $this->paidMember('premium');
        $this->actingAs($premium)->get('/membership')->assertOk()
            ->assertSee('>ACTIVE<', false)->assertSee('Manage membership')
            ->assertSee('disabled>Start Free', false)->assertSee('disabled>Choose Associate', false)
            ->assertDontSee('/register?plan=', false);

        $free = $this->member();
        $this->actingAs($free)->get('/membership')->assertOk()
            ->assertSee('Upgrade to Associate')->assertSee('Upgrade to Premium')->assertDontSee('/register?plan=', false);
    }

    public function test_charge_is_voided_when_the_payment_cannot_be_recorded(): void
    {
        $user = $this->member();
        $membership = $user->memberships()->create(['plan' => 'premium', 'status' => 'pending', 'monthly_fee' => 25]);
        $method = $user->paymentMethods()->create(['type' => 'card', 'gateway_token' => 'QA-1', 'brand' => 'Visa', 'last4' => '1111', 'exp_month' => 12, 'exp_year' => now()->year + 2, 'is_default' => true]);
        $gateway = \Mockery::mock(\App\Services\Billing\AuthorizeNet::class);
        $gateway->shouldReceive('charge')->once()->andReturn('QA-TXN-1');
        $gateway->shouldReceive('refund')->once()->with('QA-TXN-1');
        $this->app->instance(\App\Services\Billing\AuthorizeNet::class, $gateway);
        \App\Models\Payment::creating(fn () => throw new \RuntimeException('QA simulated database failure'));

        try {
            app(\App\Services\MembershipService::class)->payDues($membership, 'monthly', $method);
            $this->fail('Expected the payment to be rejected.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->assertStringContainsString('charge was cancelled', $e->errors()['payment_method'][0]);
        }
        $this->assertSame(0, \App\Models\Payment::count());
    }

    public function test_dt_held_by_a_pending_vacation_cannot_be_transferred_away(): void
    {
        $user = $this->paidMember();
        $other = $this->paidMember();
        $ledger = app(\App\Services\TokenLedger::class);
        $ledger->credit($user, 'DT', 12, 'adjusted', 'QA fixture');
        $vacation = $user->vacationRequests()->create(['partner_id' => $this->vacationProperty()->id, 'check_in' => today()->addDays(95), 'check_out' => today()->addDays(96), 'guests' => 1, 'tokens_applied' => 10, 'payment_amount' => 90]);

        $this->assertSame(2.0, $ledger->spendable($user, 'DT'));
        $this->assertSame(12.0, $ledger->spendable($user, 'DT', $vacation));
        $transfer = fn (int $quantity) => $this->actingAs($user)->postJson('/dashboard/token-transfers', ['email' => $other->email, 'token_type' => 'DT', 'quantity' => $quantity, 'indemnity' => true]);
        $transfer(5)->assertStatus(422)->assertJsonValidationErrors('quantity');
        $transfer(2)->assertCreated();

        app(\App\Services\BenefitsService::class)->confirmVacation($vacation->fresh(), 'QA');
        $this->assertSame('confirmed', $vacation->fresh()->status);
        $this->assertSame(0.0, $ledger->balance($user, 'DT'));
    }

    public function test_approved_transfer_shows_as_received_for_the_recipient(): void
    {
        $sender = $this->paidMember();
        $recipient = $this->paidMember();
        app(\App\Services\TokenLedger::class)->credit($sender, 'DT', 10, 'adjusted', 'QA fixture');
        $this->actingAs($sender)->postJson('/dashboard/token-transfers', ['email' => $recipient->email, 'token_type' => 'DT', 'quantity' => 4, 'indemnity' => true])->assertCreated();

        // Nothing arrives until the office approves it
        $this->assertSame([], $this->actingAs($recipient)->getJson('/dashboard/data')->json('ledger'));

        app(\App\Services\BenefitsService::class)->approveTransfer(\App\Models\TokenTransfer::firstOrFail());
        $entry = $this->actingAs($recipient)->getJson('/dashboard/data')->json('ledger.0');
        $this->assertSame(['Received', 4.0], [$entry['type'], (float) $entry['amt']]);
        $out = collect($this->actingAs($sender)->getJson('/dashboard/data')->json('ledger'))->firstWhere('type', 'Transferred');
        $this->assertSame(-4.0, (float) $out['amt']);
    }

    public function test_status_update_jobs_queued_before_the_action_button_still_send(): void
    {
        // A queued job is rebuilt without running the constructor, as an older serialized payload would be
        $old = (new \ReflectionClass(\App\Notifications\StatusUpdate::class))->newInstanceWithoutConstructor();
        foreach (['subject' => 'QA', 'name' => 'QA', 'lines' => ['QA only']] as $property => $value) {
            $old->{$property} = $value;
        }

        $mail = $old->toMail(new \Illuminate\Notifications\AnonymousNotifiable);
        $this->assertNull($mail->actionUrl);
        $this->assertSame(['QA only'], $mail->introLines);
    }

    public function test_member_cannot_request_the_same_property_for_overlapping_dates(): void
    {
        $user = $this->paidMember();
        $camp = $this->vacationProperty();
        $other = $this->vacationProperty();
        $ask = fn (Partner $p, int $in, int $out) => $this->actingAs($user)->postJson('/dashboard/vacations', ['property_id' => $p->id, 'check_in' => today()->addDays($in)->toDateString(), 'check_out' => today()->addDays($out)->toDateString(), 'guests' => 2]);

        $first = $ask($camp, 95, 100)->assertCreated()->json('reference');
        $ask($camp, 95, 100)->assertStatus(422)->assertJsonValidationErrors('check_in');
        $ask($camp, 98, 103)->assertStatus(422)->assertJsonPath('errors.check_in.0', "You already have a request for {$camp->name} on these dates ({$first}).");
        $ask($camp, 100, 104)->assertCreated(); // starts on the first stay's check-out day
        $ask($other, 95, 100)->assertCreated();

        \App\Models\VacationRequest::where('reference', $first)->update(['status' => 'cancelled']);
        $ask($camp, 95, 100)->assertCreated();
    }

    public function test_send_qr_to_phone_texts_a_link_without_a_dashboard_notification(): void
    {
        $user = $this->paidMember();
        $user->forceFill(['phone' => '2025550100', 'sms_opt_in' => false])->save();
        app(\App\Services\BenefitsService::class)->issueQr($user);
        config(['cospark.sms_enabled' => true]);

        $this->actingAs($user)->postJson('/dashboard/qr/send')->assertOk()->assertJsonPath('message', 'QR code link sent to your email. Turn on text messages in your profile to also get it by text.');
        $user->forceFill(['sms_opt_in' => true])->save();
        $this->actingAs($user)->postJson('/dashboard/qr/send')->assertOk()->assertJsonPath('message', 'QR code link sent to your phone by text and to your email.');

        Notification::assertSentToTimes($user, \App\Notifications\QrCodeLink::class, 2);
        Notification::assertNotSentTo($user, \App\Notifications\MemberAlert::class, fn ($n) => $n->section === 'qr');
        $notice = new \App\Notifications\QrCodeLink;
        $this->assertNotContains('database', $notice->via($user));
        $this->assertStringContainsString('/dashboard#qr', $notice->toSms($user));
    }

    public function test_member_profile_save_cannot_change_the_sign_in_email(): void
    {
        $user = $this->member();
        $original = $user->email;

        $this->actingAs($user)->postJson('/dashboard/profile', ['first' => 'Renamed', 'last' => 'Member', 'email' => 'someone.else@example.invalid'])->assertOk();

        $user->refresh();
        $this->assertSame([$original, 'Renamed'], [$user->email, $user->first_name]);
    }

    public function test_producer_profile_save_cannot_change_the_sign_in_email(): void
    {
        $producer = $this->producerFixture();
        $original = $producer->user->email;

        $this->actingAs($producer->user)->postJson('/producer/profile', ['email' => 'someone.else@example.invalid', 'phone' => '2025550100', 'business_name' => 'QA Agency'])->assertOk();

        $this->assertSame($original, $producer->user->fresh()->email);
        $this->assertSame('QA Agency', $producer->fresh()->business_name);
    }

    public function test_producer_discount_applies_to_stays_booked_from_the_member_dashboard(): void
    {
        $property = $this->vacationProperty(); // $100 a night
        $stay = ['property_id' => $property->id, 'check_in' => today()->addDays(95)->toDateString(), 'check_out' => today()->addDays(97)->toDateString(), 'guests' => 2];

        $producer = $this->producerFixture();
        $this->actingAs($producer->user)->getJson('/dashboard/data')->assertJsonPath('config.producerDiscount', 0.1);
        $this->actingAs($producer->user)->postJson('/dashboard/vacations', $stay)->assertCreated();
        $this->assertDatabaseHas('vacation_requests', ['user_id' => $producer->user_id, 'discount_rate' => 0.1, 'payment_amount' => 180]);

        $member = $this->paidMember();
        $this->actingAs($member)->getJson('/dashboard/data')->assertJsonPath('config.producerDiscount', 0);
        $this->actingAs($member)->postJson('/dashboard/vacations', $stay)->assertCreated();
        $this->assertDatabaseHas('vacation_requests', ['user_id' => $member->id, 'discount_rate' => 0, 'payment_amount' => 200]);

        $portal = $this->actingAs($producer->user)->getJson('/producer/dashboard/data')->assertOk();
        $this->assertSame(0.1, $portal->json('config.discount'));
        $this->assertEquals(100, collect($portal->json('properties'))->firstWhere('id', $property->id)['rate']);
    }

    public function test_scheduler_works_the_database_queue_inside_its_own_process(): void
    {
        config(['queue.default' => 'database']);
        dispatch(fn () => cache()->forever('qa-queued-job-ran', true))->onConnection('database');
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('jobs')->count());

        $this->artisan('schedule:run')->assertSuccessful();

        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('jobs')->count());
        $this->assertTrue(cache('qa-queued-job-ran'));
    }

    public function test_invited_sub_account_gets_its_qr_code_when_it_sets_a_password(): void
    {
        $owner = $this->paidMember('premium');
        $owner->paymentMethods()->create(['type' => 'card', 'gateway_token' => 'QA-1', 'brand' => 'Visa', 'last4' => '1111', 'exp_month' => 12, 'exp_year' => now()->year + 2, 'is_default' => true]);
        $gateway = \Mockery::mock(\App\Services\Billing\AuthorizeNet::class);
        $gateway->shouldReceive('charge')->andReturn('QA-TXN-SUB');
        $this->app->instance(\App\Services\Billing\AuthorizeNet::class, $gateway);
        config(['membership.otp.fixed_code' => '123456']);

        $invite = fn (string $email, array $permissions) => $this->actingAs($owner)->postJson('/dashboard/sub-accounts', ['name' => 'QA Sub', 'relationship' => 'Family member', 'email' => $email, 'permissions' => $permissions])->assertCreated();
        $invite('qa.sub.qr@example.invalid', ['Partner benefits', 'QR code']);
        $invite('qa.sub.noqr@example.invalid', ['View policies']);

        $withQr = User::where('email', 'qa.sub.qr@example.invalid')->firstOrFail();
        Notification::assertSentTo($withQr, \App\Notifications\SubAccountInvitation::class, fn ($n, array $channels) => $channels === ['mail']);

        foreach (['qa.sub.qr@example.invalid' => true, 'qa.sub.noqr@example.invalid' => false] as $email => $hasQr) {
            $this->app['auth']->guard('web')->logout();
            $this->postJson('/forgot-password/verify', ['email' => $email, 'code' => '123456'])->assertOk();
            $this->postJson('/reset-password', ['password' => 'QaSubPass2026', 'password_confirmation' => 'QaSubPass2026'])->assertOk();

            $sub = User::where('email', $email)->firstOrFail();
            $this->assertSame('active', $sub->status);
            $this->assertSame($hasQr, $sub->qrCode()->where('status', 'active')->exists(), $email);
        }
    }

    public function test_klaviyo_sms_skips_numbers_it_cannot_text_with_a_clear_reason(): void
    {
        config(['services.klaviyo.key' => 'QA-ONLY-NOT-A-REAL-KEY']);
        Http::fake(['a.klaviyo.com/api/events/' => Http::response([], 202)]);
        $sms = new \App\Services\Sms\KlaviyoSmsSender(app(\App\Services\Klaviyo::class));

        $sms->send('442079460958', 'QA only'); // 12 digits without "+": no country code to trust
        Http::assertNothingSent();
        $this->assertStringContainsString("can't receive texts", (string) cache(\App\Services\Klaviyo::LAST_ERROR));

        $sms->send('(202) 555-0100', 'QA only');
        Http::assertSent(function ($request) {
            $profile = json_decode($request->body())->data->attributes->profile->data->attributes;

            return is_object($profile) && $profile->phone_number === '+12025550100';
        });
    }

    public function test_approved_producer_is_emailed_working_credentials_without_a_reset_code(): void
    {
        $apply = fn (string $email) => \App\Models\ProducerApplication::create(['first_name' => 'QA', 'last_name' => 'Applicant', 'email' => $email, 'phone' => '2025550100', 'street' => 'QA ADDRESS', 'city' => 'QA', 'state' => 'MI', 'zip' => '48307', 'occupation' => 'QA', 'experience' => 'None yet', 'years_experience' => '0', 'license_status' => 'Not licensed', 'reason' => 'QA only', 'terms_accepted_at' => now()]);
        $service = app(\App\Services\ProducerService::class);

        // New applicant: a temporary password that signs in
        $service->approve($apply('qa.new.producer@example.invalid'));
        $user = User::where('email', 'qa.new.producer@example.invalid')->firstOrFail();
        $password = null;
        Notification::assertSentTo($user, \App\Notifications\ProducerWelcome::class, function ($n, array $channels) use (&$password) {
            $password = $n->password;

            return $channels === ['mail'];
        });
        $this->assertNotEmpty($password);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check($password, $user->password));
        $this->assertStringContainsString($password, implode(' ', (new \App\Notifications\ProducerWelcome($password))->toMail($user)->introLines));
        $this->assertDatabaseCount('one_time_codes', 0);

        // Existing member: keeps their own password, the email says so
        $member = $this->member();
        $before = $member->password;
        $service->approve($apply($member->email));
        Notification::assertSentTo($member, \App\Notifications\ProducerWelcome::class, fn ($n) => $n->password === null);
        $this->assertSame($before, $member->fresh()->password);
    }

    public function test_claim_form_opens_for_pending_policies_lists_them_and_takes_only_media_as_photos(): void
    {
        Storage::fake('local');
        $user = $this->member();
        $pending = $this->policy($user, 'Renters');
        $pending->update(['status' => 'pending']);
        $cancelled = $this->policy($user, 'Home');
        $cancelled->update(['status' => 'cancelled']);

        // The policy card's "File a claim" link no longer 404s for a pending policy, and the member picks a number
        $this->actingAs($user)->get('/claims/file?type=renters&policy='.$pending->id)->assertOk()
            ->assertViewHas('prefill', fn ($p) => $p['policy'] === $pending->policy_number && array_column($p['policies'], 'no') === [$pending->policy_number]);
        $this->get('/claims/file?policy='.$cancelled->id)->assertNotFound();

        $claim = ['first' => 'QA', 'last' => 'Fixture', 'email' => 'qa@example.invalid', 'phone' => '2025550100', 'pref' => 'Email', 'policy' => $pending->policy_number, 'itype' => 'Renters', 'ctype' => 'Theft', 'date' => today()->toDateString(), 'loc' => 'QA ADDRESS', 'desc' => 'QA only', 'injury' => 'No', 'rooms' => ['Kitchen'], 'habitable' => 'Yes'];
        $this->postJson('/claims', ['payload' => json_encode($claim), 'files' => ['photos' => [UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')]]])
            ->assertUnprocessable()->assertJsonPath('errors', fn ($e) => str_contains(json_encode($e), 'Photos & video'));
        $this->postJson('/claims', ['payload' => json_encode($claim), 'files' => ['photos' => [UploadedFile::fake()->createWithContent('damage.jpg', "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x00\x00\x01\x00\x01\x00\x00\xFF\xD9")], 'docs' => [UploadedFile::fake()->create('report.pdf', 10, 'application/pdf')]]])
            ->assertCreated();
    }

    public function test_producer_sign_in_opens_the_producer_tab(): void
    {
        $this->get('/producer')->assertOk()->assertSee(route('login', ['role' => 'producer']), false);
        $this->get('/login?role=producer')->assertOk()->assertSee('<input type="hidden" name="role" value="producer">', false);
        $this->get('/login')->assertOk()->assertSee('<input type="hidden" name="role" value="member">', false);
    }

    public function test_signed_in_users_go_to_their_own_portal_and_pages_are_not_cached(): void
    {
        $producer = $this->producerFixture();
        // A signed-in producer opening /login or /register lands on the producer portal, not the member dashboard
        $this->actingAs($producer->user)->get('/login')->assertRedirect(route('producer.dashboard'));
        $this->get('/insurance')->assertOk()->assertSee('href="'.route('producer.dashboard').'">My account', false);
        $this->actingAs($this->member())->get('/login')->assertRedirect(route('dashboard'));

        // Per-user pages must not be kept by the browser (Back would show another account's page)
        $cache = $this->get('/insurance')->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cache);
    }

    public function test_admin_policy_numbers_follow_the_cospark_format(): void
    {
        $admin = $this->member('admin');
        $member = $this->member();
        $policy = fn (array $extra) => $this->actingAs($admin, 'admin')->from('/admin/policy/create')->post('/admin/policy', $extra + [
            'member_email' => $member->email, 'type' => 'Renters', 'policy_number' => '', 'carrier' => 'QA Carrier', 'insured_name' => 'QA',
            'starts_on' => today()->toDateString(), 'expires_on' => today()->addYear()->toDateString(), 'annual_premium' => 100, 'amount_due' => 0, 'status' => 'active',
        ]);

        // Blank: numbered automatically, in sequence per type
        $policy([])->assertSessionHasNoErrors();
        $policy([])->assertSessionHasNoErrors();
        $this->assertSame(['CSP-REN-000001', 'CSP-REN-000002'], Policy::orderBy('id')->pluck('policy_number')->all());

        // Typed: must be the format, with the code of the chosen type (lower case is normalised)
        $policy(['policy_number' => 'lkdjas'])->assertSessionHasErrors('policy_number');
        $policy(['policy_number' => 'CSP-AUT-000010'])->assertSessionHasErrors('policy_number');
        $policy(['policy_number' => 'csp-ren-000010'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('policies', ['policy_number' => 'CSP-REN-000010']);

        // An older policy can keep the number it already has when edited
        $legacy = $this->policy($member, 'Renters');
        $legacy->update(['policy_number' => 'lkdjas']);
        $this->actingAs($admin, 'admin')->from("/admin/policy/{$legacy->id}/edit")->put("/admin/policy/{$legacy->id}", [
            'member_email' => $member->email, 'type' => 'Renters', 'policy_number' => 'lkdjas', 'carrier' => 'QA Carrier', 'insured_name' => 'QA',
            'starts_on' => today()->toDateString(), 'expires_on' => today()->addYear()->toDateString(), 'annual_premium' => 100, 'amount_due' => 0, 'status' => 'active',
        ])->assertSessionHasNoErrors();
    }

    public function test_saved_quote_email_links_back_to_the_draft(): void
    {
        $this->postJson('/quote/save', ['email' => 'qa@example.invalid', 'state' => ['first' => 'QA', 'type' => 'Auto'], 'step' => 2])->assertCreated();
        $code = \App\Models\QuoteDraft::firstOrFail()->code;

        Notification::assertSentOnDemand(\App\Notifications\StatusUpdate::class, function ($n, $channels, $notifiable) use ($code) {
            $mail = $n->toMail($notifiable);

            return $notifiable->routes['mail'] === 'qa@example.invalid' && str_contains($mail->actionUrl, "resume={$code}");
        });
    }

    public function test_login_tab_must_match_the_account_role(): void
    {
        foreach ([['member', 'producer', 'This is a Member account'], ['producer', 'member', 'This is a Producer account']] as [$role, $tab, $message]) {
            $user = $this->member($role);
            $this->post('/login', ['email' => $user->email, 'password' => 'Test-only-password!26', 'role' => $tab])
                ->assertSessionHasErrors(['email' => $message.'. Select '.ucfirst($role).' to sign in.']);
            $this->assertGuest();

            $this->post('/login', ['email' => $user->email, 'password' => 'Test-only-password!26', 'role' => $role]);
            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_klaviyo_outage_is_reported_without_failing_the_users_action(): void
    {
        config(['services.klaviyo.key' => 'QA-ONLY-NOT-A-REAL-KEY']);
        Http::fake(['a.klaviyo.com/api/*' => Http::response(['errors' => [['detail' => 'QA outage']]], 503)]);
        app(\App\Services\Sms\KlaviyoSmsSender::class)->send('+12025550100', 'QA only');
        Http::assertSentCount(1);
        $this->assertStringContainsString('QA outage', \App\Support\SystemStatus::summary()['klaviyoError'], 'admins can see why Klaviyo rejected a message');
    }

    public function test_pending_stays_do_not_shrink_a_new_stay_estimate_and_dt_leave_only_on_confirmation(): void
    {
        $user = $this->paidMember();
        $ledger = app(\App\Services\TokenLedger::class);
        $ledger->credit($user, 'DT', 36, 'adjusted', 'QA fixture');
        $stay = $this->vacationProperty();
        foreach ([100, 130] as $days) {
            $in = today()->addDays($days);
            $this->actingAs($user)->postJson('/dashboard/vacations', ['property_id' => $stay->id, 'check_in' => $in->toDateString(), 'check_out' => $in->copy()->addDays(2)->toDateString(), 'guests' => 2])->assertCreated();
        }
        [$first, $second] = $user->vacationRequests()->orderBy('id')->get();
        $this->assertSame([36, 36], [(int) $first->tokens_applied, (int) $second->tokens_applied], 'each pending request quotes the full balance');
        $this->assertSame(36.0, $ledger->balance($user, 'DT'), 'nothing is deducted before confirmation');
        $this->assertSame(0.0, $ledger->spendable($user, 'DT'), 'quoted DT are still kept out of transfers');

        $benefits = app(\App\Services\BenefitsService::class);
        $benefits->confirmVacation($first, null);
        $this->assertSame(0.0, $ledger->balance($user->fresh(), 'DT'));
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $benefits->confirmVacation($second->fresh(), null);
    }

    public function test_claims_verify_the_policy_number_owner_type_and_period(): void
    {
        $member = $this->member();
        $home = $this->policy($member, 'Home');
        $auto = $this->policy($member, 'Auto');
        $other = $this->policy($this->member(), 'Auto');
        $base = ['first' => 'QA', 'last' => 'Fixture', 'email' => 'qa@example.invalid', 'phone' => '2025550100', 'pref' => 'Email', 'date' => today()->toDateString(), 'loc' => 'QA ADDRESS', 'desc' => 'QA only',
            'itype' => 'Auto', 'ctype' => 'Glass', 'vehicle' => 'QA car', 'police' => 'No', 'other' => 'No', 'drivable' => 'Yes', 'injury' => 'No'];
        $file = fn (array $data) => $this->postJson('/claims', ['payload' => json_encode($data + $base)]);

        $file(['policy' => 'CSP-DOES-NOT-EXIST'])->assertUnprocessable()->assertJsonValidationErrors('policy');
        $file(['policy' => $home->policy_number])->assertUnprocessable()->assertJsonValidationErrors(['itype' => 'is a Home policy']);
        $file(['policy' => $auto->policy_number, 'date' => today()->subYears(2)->toDateString()])->assertUnprocessable()->assertJsonValidationErrors(['date' => 'policy period']);
        $this->actingAs($member);
        $file(['policy' => $other->policy_number])->assertUnprocessable()->assertJsonValidationErrors(['policy' => 'not on your account']);
        $file(['policy' => strtolower($auto->policy_number)])->assertCreated();
        $this->assertDatabaseHas('claims', ['policy_id' => $auto->id, 'policy_number' => $auto->policy_number, 'user_id' => $member->id]);
        $this->assertDatabaseCount('claims', 1);
    }

    public function test_daily_job_sends_policy_renewal_and_payment_alerts_and_expires_lapsed_policies(): void
    {
        $user = $this->member();
        $renewing = $this->policy($user, 'Home');
        $renewing->update(['expires_on' => today()->addDays(30)]);
        $due = $this->policy($user, 'Auto');
        $due->update(['amount_due' => 120, 'due_on' => today()->addDays(7)]);
        $lapsed = $this->policy($user, 'Renters');
        $lapsed->update(['starts_on' => today()->subYear()->subDay(), 'expires_on' => today()->subDay()]);

        $this->artisan('billing:daily')->assertSuccessful();

        Notification::assertSentToTimes($user, \App\Notifications\MemberAlert::class, 3);
        $this->assertSame('expired', $lapsed->fresh()->status);
        $this->assertSame('active', $renewing->fresh()->status);
    }

    public function test_qr_codes_are_issued_only_to_eligible_members(): void
    {
        $benefits = app(\App\Services\BenefitsService::class);
        $this->assertNotNull($benefits->issueQr($this->paidMember())->code);
        foreach ([$this->member(), tap($this->paidMember())->update(['status' => 'suspended']), User::create(['first_name' => 'QA', 'last_name' => 'Staff', 'email' => 'qa-staff@example.invalid', 'password' => 'x-Test-only-26', 'role' => 'admin', 'status' => 'active'])] as $user) {
            try {
                $benefits->issueQr($user->fresh());
                $this->fail("QR issued to ineligible {$user->email}");
            } catch (\Illuminate\Validation\ValidationException) {
                $this->assertDatabaseMissing('member_qr_codes', ['user_id' => $user->id]);
            }
        }
    }

    public function test_business_hour_appointments_only_on_office_days(): void
    {
        $base = ['type' => 'phone', 'a_name' => 'QA Fixture', 'a_email' => 'qa@example.invalid', 'a_phone' => '2025550100'];
        $saturday = today()->next('Saturday')->toDateString();
        $this->postJson('/appointments', $base + ['a_date' => $saturday, 'a_time' => '9:00 AM'])->assertUnprocessable()->assertJsonValidationErrors(['a_time' => 'Monday, Tuesday, Wednesday and Thursday']);
        $this->postJson('/appointments', $base + ['a_date' => $saturday, 'a_time' => last(config('cospark.appointment_times'))])->assertCreated();
        $this->postJson('/appointments', $base + ['a_date' => today()->next('Tuesday')->toDateString(), 'a_time' => '9:00 AM'])->assertCreated();
    }

    public function test_transfer_requests_reach_the_transfer_mailbox(): void
    {
        config(['cospark.office.token_transfer_email' => 'transfers@example.invalid']);
        $sender = $this->paidMember();
        $recipient = $this->paidMember();
        app(\App\Services\TokenLedger::class)->credit($sender, 'DT', 30, 'adjusted', 'QA fixture');
        $this->actingAs($sender)->postJson('/dashboard/token-transfers', ['email' => $recipient->email, 'token_type' => 'DT', 'quantity' => 5, 'indemnity' => true])->assertCreated();
        Notification::assertSentOnDemand(\App\Notifications\NewSubmission::class, fn ($n, $channels, $notifiable) => $notifiable->routes['mail'] === 'transfers@example.invalid' && $n->summary['Tokens'] === '5 DT');
    }

    public function test_admin_sees_system_status_and_can_send_queued_messages_without_a_cron(): void
    {
        $admin = $this->member('admin');
        \Illuminate\Support\Facades\Queue::connection('database')->push(new \Illuminate\Queue\CallQueuedClosure(new \Laravel\SerializableClosure\SerializableClosure(fn () => cache(['qa.queue.ran' => true]))));
        config(['queue.default' => 'database']);
        $version = (string) app(\App\Http\Middleware\HandleInertiaRequests::class)->version(request());

        $system = $this->actingAs($admin, 'admin')->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->get('/admin')->assertOk()->json('props.system');
        $this->assertFalse($system['cronOk']);
        $this->assertSame(1, $system['pending']);

        $this->actingAs($admin, 'admin')->from('/admin')->post('/admin/system/queue')->assertRedirect('/admin')->assertSessionHas('success');
        $this->assertTrue(cache('qa.queue.ran'));
        $this->assertDatabaseCount('jobs', 0);
    }

    public function test_secret_cron_url_runs_the_scheduler_and_is_hidden_without_the_token(): void
    {
        config(['app.cron_token' => null]);
        $this->get('/cron/anything')->assertNotFound();
        config(['app.cron_token' => 'qa-secret-token']);
        $this->get('/cron/wrong-token')->assertNotFound();
        $this->get('/cron/qa-secret-token')->assertOk();
        $this->assertNotNull(cache(\App\Support\SystemStatus::HEARTBEAT), 'scheduler heartbeat recorded');
    }
}
