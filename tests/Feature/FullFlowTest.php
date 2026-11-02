<?php

namespace Tests\Feature;

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Appointment;
use App\Models\Claim;
use App\Models\Lead;
use App\Models\MemberQrCode;
use App\Models\Partner;
use App\Models\Policy;
use App\Models\ProducerApplication;
use App\Models\TokenTransfer;
use App\Models\User;
use App\Models\VacationRequest;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\MembershipPlanSeeder;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * End-to-end flows across the three portals: what Admin does must show up in the Member and
 * Producer dashboards, and CMS/settings edits must show up on the public pages.
 */
class FullFlowTest extends TestCase
{
    use RefreshDatabase;

    private const PASSWORD = 'Test-only-password!26';

    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
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
        Storage::fake('local');
        $this->seed([CatalogSeeder::class, MembershipPlanSeeder::class, PageSeeder::class]);
    }

    private function user(string $role = 'member', string $plan = 'premium'): User
    {
        $user = User::create(['first_name' => 'Flow', 'last_name' => ucfirst($role), 'email' => uniqid("flow-$role-").'@example.com', 'phone' => '(248) 555-0142', 'password' => self::PASSWORD, 'role' => $role, 'status' => 'active']);
        $user->forceFill(['email_verified_at' => now()])->save();
        if ($role !== 'admin') {
            $user->memberships()->create(['plan' => $plan, 'status' => 'active', 'monthly_fee' => $plan === 'premium' ? 25 : 8.88, 'started_at' => now()]);
        }

        return $user;
    }

    private function admin(): static
    {
        return $this->actingAs($this->admin ??= $this->user('admin'), 'admin');
    }

    private ?User $admin = null;

    private function action(string $resource, $id, string $action, array $fields = []): void
    {
        $this->done($this->admin()->from('/admin')->post("/admin/$resource/$id/actions/$action", $fields), "$resource.$action");
    }

    /** An admin write must redirect back with no validation errors and no error flash. */
    private function done(TestResponse $response, string $what): void
    {
        $problem = $response->exception?->getMessage() ?? session('error') ?? (session('errors')?->any() ? json_encode(session('errors')->all()) : null);
        $this->assertTrue($response->isRedirect() && ! $problem, "$what failed: HTTP {$response->status()} ".($problem ?? ''));
    }

    /** The edit form's current values, as the admin Vue form would submit them back. */
    private function formValues(string $uri): array
    {
        $version = (string) app(HandleInertiaRequests::class)->version(request());

        return $this->admin()->withHeaders(['X-Inertia' => 'true', 'X-Inertia-Version' => $version])->get($uri)->assertOk()->json('props.values');
    }

    /** Back to an anonymous website visitor (admin and portal sessions are separate guards). */
    private function guest(): static
    {
        auth()->guard('web')->logout();
        auth()->shouldUse('web');

        return $this;
    }

    private function memberData(User $member): string
    {
        return $this->actingAs($member, 'web')->getJson('/dashboard/data')->assertOk()->getContent();
    }

    private function producerData(User $producer): string
    {
        return $this->actingAs($producer, 'web')->getJson('/producer/dashboard/data')->assertOk()->getContent();
    }

    public function test_member_lifecycle_between_admin_and_member_dashboard(): void
    {
        $member = $this->user();
        $ledger = app(\App\Services\TokenLedger::class);

        // Admin issues a policy → it appears on the member's dashboard with a notification
        $this->done($this->admin()->post('/admin/policy', [
            'member_email' => $member->email, 'type' => 'Auto', 'policy_number' => 'CSP-AUT-000901', 'carrier' => 'Flow Mutual',
            'insured_name' => 'Flow Member', 'covers' => '', 'starts_on' => today()->toDateString(), 'expires_on' => today()->addYear()->toDateString(),
            'annual_premium' => 1200, 'amount_due' => 100, 'due_on' => today()->addMonth()->toDateString(), 'status' => 'active', 'renewal_note' => '',
        ]), 'policy.create');
        $policy = Policy::where('policy_number', 'CSP-AUT-000901')->firstOrFail();
        $this->assertSame($member->id, $policy->user_id);
        $this->assertStringContainsString('CSP-AUT-000901', $this->memberData($member));

        // Admin uploads a policy document → member can see and download it
        $this->action('policy', $policy->id, 'document', ['name' => 'Flow declarations', 'file' => UploadedFile::fake()->create('dec.pdf', 12, 'application/pdf')]);
        $this->assertStringContainsString('Flow declarations', $this->memberData($member));

        // Premium paid to the carrier and recorded by admin → IT for this policy year (Premium 7%), credited once
        $this->action('policy', $policy->id, 'premium', ['amount' => 100, 'paid_on' => today()->toDateString(), 'reference' => 'CARRIER-1']);
        $this->assertSame(84, (int) $ledger->balance($member->fresh(), 'IT'));
        $this->assertEquals(0, $policy->fresh()->amount_due);
        $this->action('policy', $policy->id, 'premium', ['amount' => 20, 'paid_on' => today()->toDateString()]);
        $this->assertSame(84, (int) $ledger->balance($member->fresh(), 'IT'), 'IT once per policy year');
        $this->assertStringContainsString('Premium payment recorded', $this->memberData($member));

        // Member files a claim on that policy → admin moves it along → member sees status, action and message
        $this->actingAs($member, 'web')->postJson('/claims', ['payload' => json_encode([
            'first' => 'Flow', 'last' => 'Member', 'email' => $member->email, 'phone' => '2485550142', 'pref' => 'Email', 'policy' => 'CSP-AUT-000901',
            'itype' => 'Auto', 'ctype' => 'Glass', 'date' => today()->toDateString(), 'loc' => 'Rochester, MI', 'desc' => 'Cracked windshield',
            'vehicle' => 'Flow car', 'police' => 'No', 'other' => 'No', 'drivable' => 'Yes', 'injury' => 'No',
        ])])->assertCreated();
        $claim = Claim::latest('id')->firstOrFail();
        $this->assertSame($member->id, $claim->user_id);
        $this->admin()->from('/admin')->put("/admin/claim/{$claim->id}", ['status' => 'awaiting_documents', 'progress_step' => 2, 'required_action' => 'Upload repair estimate FLOW', 'outstanding_documents' => 'Repair estimate'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->action('claim', $claim->id, 'message', ['author' => 'Claims team', 'body' => 'Flow adjuster message']);
        $data = $this->memberData($member);
        $this->assertStringContainsString('Upload repair estimate FLOW', $data);
        $this->assertStringContainsString('Flow adjuster message', $data);
        $this->assertGreaterThan(0, $member->notifications()->count(), 'member gets portal notifications');

        // Admin token adjustment → wallet
        $this->action('user', $member->id, 'adjust', ['type' => 'DT', 'amount' => 300, 'reason' => 'Flow credit']);
        $this->assertSame(300, (int) $ledger->balance($member->fresh(), 'DT'));

        // Member requests a stay → admin confirms → DT deducted and status visible
        $stay = Partner::create(['name' => 'Flow Stay', 'slug' => 'flow-stay', 'category' => 'Vacation', 'description' => 'Flow', 'image_url' => 'https://example.com/s.png', 'is_vacation' => true, 'is_active' => true, 'nightly_rate' => 285, 'tokens_per_night' => 45]);
        $in = today()->addDays(100);
        $this->actingAs($member, 'web')->postJson('/dashboard/vacations', ['property_id' => $stay->id, 'check_in' => $in->toDateString(), 'check_out' => $in->copy()->addDays(3)->toDateString(), 'guests' => 2, 'notes' => 'Flow'])->assertCreated();
        $vacation = VacationRequest::latest('id')->firstOrFail();
        $this->action('vacation-request', $vacation->id, 'confirm', ['instructions' => 'Flow check-in code 1234']);
        $this->assertSame('confirmed', $vacation->fresh()->status);
        $this->assertSame(300 - 135, (int) $ledger->balance($member->fresh(), 'DT'));
        $this->assertStringContainsString('Flow check-in code 1234', $this->memberData($member));

        // Member transfers DT → admin approves → recipient credited
        $recipient = $this->user();
        $this->actingAs($member, 'web')->postJson('/dashboard/token-transfers', ['email' => $recipient->email, 'token_type' => 'DT', 'quantity' => 15, 'indemnity' => true])->assertCreated();
        $this->action('token-transfer', TokenTransfer::latest('id')->value('id'), 'approve');
        $this->assertSame(15, (int) $ledger->balance($recipient->fresh(), 'DT'));
        $this->assertSame(150, (int) $ledger->balance($member->fresh(), 'DT'));

        // Admin issues a QR → partner redemption at the scanner → member sees it
        $this->action('user', $member->id, 'qr');
        $code = MemberQrCode::where('user_id', $member->id)->value('code');
        $this->assertNotNull($code);
        $partner = Partner::create(['name' => 'Flow Partner', 'slug' => 'flow-partner', 'category' => 'Dining', 'description' => 'Flow', 'image_url' => 'https://example.com/p.png', 'is_vacation' => false, 'is_active' => true]);
        $this->admin()->from('/admin/qr-scan')->post('/admin/qr-scan', ['code' => $code, 'partner_id' => $partner->id, 'amount' => 10, 'location' => 'Flow desk'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertStringContainsString('Flow Partner', $this->memberData($member));

        // Member books an appointment → admin confirms → member sees confirmed
        $this->actingAs($member, 'web')->postJson('/appointments', ['type' => 'phone', 'a_date' => today()->next('Monday')->toDateString(), 'a_time' => '10:30 AM', 'a_name' => 'Flow Member', 'a_email' => $member->email, 'a_phone' => '(248) 555-0142'])->assertCreated();
        $appointment = Appointment::latest('id')->firstOrFail();
        $this->assertSame($member->id, $appointment->user_id);
        $this->action('appointment', $appointment->id, 'confirmed');
        $this->assertTrue($member->notifications()->get()->contains(fn ($n) => ($n->data['cat'] ?? null) === 'Appointments'), 'member is notified in the portal');
        $this->assertSame('confirmed', $appointment->fresh()->status);
        $this->assertStringContainsString('"confirmed"', strtolower($this->memberData($member)));

        // Mid-year policy cancellation forfeits this policy year's IT; claims on it are then refused
        $this->action('policy', $policy->id, 'cancel');
        $this->assertSame(0, (int) $ledger->balance($member->fresh(), 'IT'));
        $this->actingAs($member, 'web')->postJson('/claims', ['payload' => json_encode(['first' => 'Flow', 'last' => 'Member', 'email' => $member->email, 'phone' => '2485550142', 'pref' => 'Email', 'policy' => 'CSP-AUT-000901', 'itype' => 'Auto', 'ctype' => 'Glass', 'date' => today()->toDateString(), 'loc' => 'X', 'desc' => 'X', 'vehicle' => 'X', 'police' => 'No', 'other' => 'No', 'drivable' => 'Yes', 'injury' => 'No'])])
            ->assertUnprocessable()->assertJsonValidationErrors(['policy' => 'cancelled']);

        // Suspension locks the portal; reactivation restores it
        $this->action('user', $member->id, 'suspend');
        $this->actingAs($member->fresh(), 'web')->getJson('/dashboard/data')->assertStatus(403);
        $this->action('user', $member->id, 'activate');
        $this->actingAs($member->fresh(), 'web')->getJson('/dashboard/data')->assertOk();
    }

    public function test_producer_lifecycle_between_admin_and_producer_portal(): void
    {
        // Public application → admin approves → producer account and profile exist
        $email = 'flow-applicant@example.com';
        $this->guest()->post('/producer/apply', ['payload' => json_encode(['first' => 'Flow', 'last' => 'Applicant', 'email' => $email, 'phone' => '2485550142', 'street' => '1 Main St', 'city' => 'Rochester', 'st' => 'MI', 'zip' => '48307', 'occ' => 'Agent', 'exp' => 'None yet', 'years' => '0', 'lic' => 'Not licensed', 'why' => 'Flow', 'resume' => ['cv.pdf']]),
            'files' => ['resume' => [UploadedFile::fake()->create('cv.pdf', 10, 'application/pdf')]]], ['Accept' => 'application/json'])->assertCreated();
        $this->action('producer-application', ProducerApplication::latest('id')->value('id'), 'approve');
        $user = User::where('email', $email)->firstOrFail();
        $this->assertSame('producer', $user->role);
        $producer = $user->producer()->firstOrFail();

        // Onboarding = verified email + paid Associate membership (gateway not exercised here)
        $user->forceFill(['email_verified_at' => now(), 'password' => self::PASSWORD])->save();
        $user->memberships()->create(['plan' => 'associate', 'status' => 'active', 'monthly_fee' => 8.88, 'started_at' => now()]);
        $this->guest()->post('/login', ['email' => $email, 'password' => self::PASSWORD, 'role' => 'producer'])->assertRedirect(route('producer.dashboard'));
        $this->get('/producer/dashboard')->assertOk();
        auth()->logout();

        // A quote lead assigned by admin shows in the producer's leads; the producer works it
        $this->guest()->postJson('/quote/quick', ['cov' => 'Auto', 'zip' => '48307', 'first' => 'Lead', 'last' => 'Flow', 'email' => 'lead-flow@example.com', 'phone' => '2485550199', 'street' => '2 Main St', 'city' => 'Rochester', 'st' => 'MI'])->assertCreated();
        $lead = Lead::latest('id')->firstOrFail();
        $this->admin()->from('/admin')->put("/admin/lead/{$lead->id}", ['producer_id' => $producer->id, 'status' => 'new', 'follow_up_on' => null, 'producer_notes' => 'Flow handoff'])
            ->assertRedirect()->assertSessionHasNoErrors();
        $this->assertStringContainsString($lead->reference, $this->producerData($user));
        $this->assertGreaterThan(0, $user->notifications()->count(), 'producer notified of the lead');
        $this->actingAs($user, 'web')->postJson("/producer/leads/{$lead->reference}", ['status' => 'contacted', 'notes' => 'Called'])->assertOk();
        $this->assertSame('contacted', $lead->fresh()->status);

        // Admin records a commission → producer sees it; marking it paid shows too
        $this->action('producer', $producer->id, 'commission', ['reference' => 'POL-FLOW-1', 'insured' => 'Flow Insured', 'line' => 'Auto', 'written_on' => today()->toDateString(), 'premium' => 1500, 'commission_base' => 150]);
        $this->assertStringContainsString('POL-FLOW-1', $this->producerData($user));
        $commission = $producer->commissions()->latest('id')->firstOrFail();
        $this->action('commission', $commission->id, 'paid', ['paid_on' => today()->toDateString()]);
        $this->assertNotNull($commission->fresh()->paid_on);

        // Admin requests a document → producer uploads → admin verifies
        $this->action('producer', $producer->id, 'request_document', ['name' => 'Flow W-9']);
        $this->assertStringContainsString('Flow W-9', $this->producerData($user));
        $document = $producer->documents()->where('name', 'Flow W-9')->firstOrFail();
        $this->actingAs($user, 'web')->post('/producer/documents', ['document_id' => $document->id, 'file' => UploadedFile::fake()->create('w9.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertSuccessful();
        $this->action('producer', $producer->id, 'review_document', ['document_id' => $document->id, 'decision' => 'verified']);
        $this->assertSame('verified', $document->fresh()->status);

        // Producer adds a licence → admin verifies it
        $this->actingAs($user, 'web')->post('/producer/licenses', ['license_type' => 'Property & Casualty', 'state' => 'MI', 'license_number' => 'FLOW-LIC-1', 'expires_on' => today()->addYear()->toDateString(), 'document' => UploadedFile::fake()->create('lic.pdf', 10, 'application/pdf')], ['Accept' => 'application/json'])->assertSuccessful();
        $license = $producer->licenses()->latest('id')->firstOrFail();
        $this->assertSame('not_credited', $producer->fresh()->bonus_status, 'no bonus before an active licence');
        $this->action('producer', $producer->id, 'verify_license', ['license_id' => $license->id]);
        $this->assertSame('locked', $producer->fresh()->bonus_status, 'licensed + $8.88 membership → 2,000 credited, locked for 12 months');
        $this->assertSame(2000, (int) $producer->fresh()->bonus_tokens);
        $this->assertStringContainsString('FLOW-LIC-1', $this->producerData($user));
        $this->assertNotNull($license->fresh()->verified_at);
    }

    public function test_cms_plans_and_settings_edits_show_on_public_pages(): void
    {
        // Page content
        $page = \App\Models\Page::query()->orderBy('id')->firstOrFail();
        $values = $this->formValues("/admin/page/{$page->id}/edit");
        $values['hero_title'] = 'Flow edited hero title';
        $this->admin()->from('/admin')->put("/admin/page/{$page->id}", $values)->assertRedirect()->assertSessionHasNoErrors();
        $this->guest()->get('/claims')->assertOk()->assertSee('Flow edited hero title');

        // Membership plan name and price → membership and registration pages
        $plan = \App\Models\MembershipPlan::query()->where('monthly_fee', '>', 20)->firstOrFail();
        $values = $this->formValues("/admin/membership-plan/{$plan->id}/edit");
        $values['name'] = 'Flow Premium';
        $values['monthly_fee'] = 27.5;
        $this->admin()->from('/admin')->put("/admin/membership-plan/{$plan->id}", $values)->assertRedirect()->assertSessionHasNoErrors();
        $this->guest()->get('/membership')->assertOk()->assertSee('Flow Premium')->assertSee('data-p="27.5"', false);
        $this->get('/register')->assertOk()->assertSee('Flow Premium');

        // Insurance product and article copy → their public pages
        $product = \App\Models\InsuranceProduct::where('slug', 'car')->firstOrFail();
        $values = $this->formValues("/admin/insurance-product/{$product->id}/edit");
        $values[array_key_exists('name', $values) ? 'name' : 'title'] = 'Flow Car Cover';
        $this->done($this->admin()->from('/admin')->put("/admin/insurance-product/{$product->id}", $values), 'product.update');
        $this->guest()->get('/insurance/car')->assertOk()->assertSee('Flow Car Cover');
        $article = \App\Models\Resource::where('slug', 'how-much-auto')->firstOrFail();
        $values = $this->formValues("/admin/article/{$article->id}/edit");
        $values['title'] = 'Flow article headline';
        $values['body'] = $values['body'] ?: 'Flow article body text.';
        $this->done($this->admin()->from('/admin')->put("/admin/article/{$article->id}", $values), 'article.update');
        $this->guest()->get('/resources/how-much-auto')->assertOk()->assertSee('Flow article headline');
        $this->get('/resources')->assertOk()->assertSee('Flow article headline');

        // A partner added in admin → ecosystem page
        $this->done($this->admin()->from('/admin')->post('/admin/partner', ['name' => 'Flow Partner Co', 'slug' => 'flow-partner-co', 'category' => 'Dining', 'description' => 'Flow partner perks', 'image_url' => 'https://example.com/p.png', 'is_vacation' => false, 'is_active' => true, 'sort_order' => 1, 'website_url' => 'https://example.com']), 'partner.create');
        $this->guest()->get('/ecosystem')->assertOk()->assertSee('Flow Partner Co');

        // Site settings → contact page and footer
        $settings = $this->formValues('/admin/settings');
        $settings['office__phone'] = '(248) 555-0777';
        $this->admin()->from('/admin/settings')->post('/admin/settings', $settings)->assertRedirect()->assertSessionHasNoErrors();
        $this->guest()->get('/contact')->assertOk()->assertSee('(248) 555-0777');
        $this->get('/')->assertOk()->assertSee('(248) 555-0777');
    }
}
