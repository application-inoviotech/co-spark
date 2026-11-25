<?php

namespace Tests\Feature;

use App\Admin\Resources\InsuranceProductResource;
use App\Models\InsuranceProduct;
use App\Models\User;
use App\Support\InsuranceContent;
use Database\Seeders\CatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class InsuranceContentReviewTest extends TestCase
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
        $this->seed(CatalogSeeder::class);
    }

    private function copy(string $slug): array
    {
        return app(InsuranceContent::class)->forProduct(InsuranceProduct::where('slug', $slug)->firstOrFail());
    }

    public function test_all_catalog_pages_render_and_financial_pages_use_services_and_fees(): void
    {
        foreach (InsuranceProduct::all() as $product) {
            $this->get('/insurance/'.$product->slug)->assertOk()->assertViewHas('copy');
        }
        foreach (['financial-solutions', 'asset-management'] as $slug) {
            $this->get('/insurance/'.$slug)->assertSee('Services and planning.')
                ->assertSee('What fees and expenses apply?')->assertSee('Discuss your goals')
                ->assertDontSee('How do I file a claim?')->assertDontSee('your deductible')
                ->assertDontSee('Ready to price');
        }
    }

    public function test_life_faqs_and_permanent_life_options_are_distinct(): void
    {
        foreach (['life', 'term-life', 'whole-life', 'universal-life'] as $slug) {
            $faqs = $this->copy($slug)['faqs'];
            $this->assertStringContainsString('age, health', $faqs[0][1]);
            $this->assertStringContainsString('does not have a deductible', $faqs[0][1]);
            $this->assertStringContainsString('priced separately', $faqs[1][1]);
        }
        $this->assertNotContains('Cash value', array_column($this->copy('term-life')['coverage'], 'title'));
        foreach (['whole-life', 'universal-life'] as $slug) {
            $this->assertNotContains('Level or decreasing term', $this->copy($slug)['options']);
            $this->assertNotContains('Return of premium', $this->copy($slug)['options']);
        }
    }

    public function test_property_products_do_not_inherit_unrelated_homeowners_coverages(): void
    {
        foreach (['renters', 'auto-renters', 'condo', 'landlord', 'vacant-home'] as $slug) {
            $titles = array_column($this->copy($slug)['coverage'], 'title');
            $this->assertNotContains('Dwelling', $titles, $slug);
            $this->assertNotContains('Loss of use', $titles, $slug);
        }
        $this->assertStringContainsString('HOA master policy', json_encode($this->copy('condo')['coverage']));
        $this->assertContains('Loss of rental income', array_column($this->copy('landlord')['coverage'], 'title'));
        $this->assertStringContainsString('Tenants need their own renters insurance', json_encode($this->copy('landlord')['coverage']));

        foreach (['flood', 'earthquake'] as $slug) {
            $titles = array_column($this->copy($slug)['coverage'], 'title');
            $this->assertNotContains('Personal liability', $titles);
            $this->assertNotContains('Medical payments', $titles);
            foreach (['Replacement cost on contents', 'Water backup', 'Scheduled jewelry & valuables', 'Identity protection', 'Equipment breakdown'] as $wrong) {
                $this->assertNotContains($wrong, $this->copy($slug)['options']);
            }
        }
        $flood = $this->copy('flood');
        $this->assertStringContainsString('actual cash value', json_encode($flood['coverage']));
        $this->assertNotContains('Bundle with auto and save', $flood['benefits']);
        $this->assertStringContainsString('not discounted', $flood['faqs'][1][1]);
    }

    public function test_vehicle_and_commercial_products_use_their_own_coverage(): void
    {
        $boat = $this->copy('boat');
        $this->assertContains('Hull and equipment', array_column($boat['coverage'], 'title'));
        $this->assertContains('Uninsured boater', array_column($boat['coverage'], 'title'));
        $this->assertContains('On-water towing', array_column($boat['coverage'], 'title'));
        foreach (['boat', 'motorcycle', 'atv', 'other-rec'] as $slug) {
            $text = json_encode($this->copy($slug));
            $this->assertStringNotContainsString('Glass with zero deductible', $text);
            $this->assertStringNotContainsString('a rental while yours is repaired', $text);
        }
        $this->assertNotContains('Agreed or stated value', $this->copy('car')['options']);
        $firstTitles = [];
        foreach (['commercial-auto', 'commercial-property', 'workers-comp', 'general-liability'] as $slug) {
            $firstTitles[] = $this->copy($slug)['coverage'][0]['title'];
            $this->assertCount(4, $this->copy($slug)['faqs']);
        }
        $this->assertCount(4, array_unique($firstTitles));
        $workers = $this->copy('workers-comp')['faqs'];
        $this->assertStringContainsString('payroll, job classifications', $workers[0][1]);
        $this->assertStringContainsString('do not have a deductible', $workers[0][1]);
        $this->assertStringContainsString('rated separately', $workers[1][1]);
    }

    public function test_partner_plans_and_reward_wording_are_product_specific(): void
    {
        $firstTitles = [];
        foreach (['medicare', 'health', 'pet', 'device-protection'] as $slug) {
            $copy = $this->copy($slug);
            $firstTitles[] = $copy['coverage'][0]['title'];
            if (in_array($slug, ['medicare', 'health'], true)) {
                $text = json_encode($copy);
                $this->assertStringNotContainsString('5%', $text);
                $this->assertStringNotContainsString('7%', $text);
                $this->assertStringNotContainsString('Repairs & replacement', $text);
                $this->assertStringContainsString('No percentage-of-premium reward is promised', $text);
            }
        }
        $this->assertCount(4, array_unique($firstTitles));
        $this->assertStringContainsString('not discounted', $this->copy('medicare')['faqs'][1][1]);
        $this->assertStringNotContainsString('routine care or maintenance', json_encode($this->copy('device-protection')));
    }

    public function test_claim_articles_bundle_questions_and_initialisms(): void
    {
        $this->get('/claims')->assertOk()->assertSee('File an auto claim')->assertSee('File an other claim')
            ->assertDontSee('File a auto claim')->assertDontSee('File a other claim');
        foreach (['auto-home', 'auto-renters'] as $slug) {
            $this->assertStringContainsString('Can I add other policies', $this->copy($slug)['faqs'][1][0]);
        }
        $this->get('/insurance/atv')->assertSee('How much does ATV &amp; off-road coverage cost?', false);
        $this->get('/insurance/rv')->assertSee('How much does RV &amp; motor home coverage cost?', false);
    }

    public function test_admin_can_save_product_copy_without_changing_sibling_products_and_clear_overrides(): void
    {
        $admin = User::create(['first_name' => 'Content', 'last_name' => 'Reviewer', 'email' => 'review@example.invalid', 'password' => 'Test-only-password!26', 'role' => 'admin', 'status' => 'active']);
        $product = InsuranceProduct::where('slug', 'term-life')->firstOrFail();
        $siblingBefore = $this->copy('whole-life');
        $data = $product->only(['name', 'slug', 'insurance_category_id', 'quote_type', 'tagline', 'intro', 'image_url', 'sort_order', 'is_active']);
        $data += ['coverage_lines' => 'Reviewed benefit | Product-specific description', 'options' => "Choice one\nChoice two", 'benefits' => 'Confirmed benefit', 'faq_lines' => 'Reviewed question? | Reviewed answer.'];

        $this->actingAs($admin, 'admin')->get('/admin/insurance-product/'.$product->id.'/edit')
            ->assertOk()->assertSee('Product FAQs')->assertSee('Coverage for a set term');
        $this->actingAs($admin, 'admin')->put('/admin/insurance-product/'.$product->id, $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->get('/insurance/term-life')->assertSee('Product-specific description')->assertSee('Reviewed answer.');
        $this->assertSame($siblingBefore, $this->copy('whole-life'));
        $resource = new InsuranceProductResource;
        $this->assertSame(['Reviewed question? | Reviewed answer.'], $resource->value($product->fresh(), 'faq_lines'));

        foreach (['coverage_lines', 'options', 'benefits', 'faq_lines'] as $field) {
            $data[$field] = '';
        }
        $this->put('/admin/insurance-product/'.$product->id, $data)->assertSessionHasNoErrors();
        $this->assertNull($product->fresh()->content);
        $this->get('/insurance/term-life')->assertSee('Coverage for a set term')->assertDontSee('Reviewed answer.');

        $rules = $resource->rules($product);
        $this->assertTrue(Validator::make(['faq_lines' => 'Missing answer separator'], ['faq_lines' => $rules['faq_lines']])->fails());
        $this->assertTrue(Validator::make(['coverage_lines' => 'Empty description | '], ['coverage_lines' => $rules['coverage_lines']])->fails());
    }
}
