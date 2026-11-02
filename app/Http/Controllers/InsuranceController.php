<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCategory;
use App\Models\InsuranceProduct;
use Illuminate\View\View;

class InsuranceController extends Controller
{
    /** Products shown as cross-category suggestions under "Related coverage". */
    private const FEATURED_SLUGS = ['car', 'homeowners', 'life', 'business'];

    public function index(): View
    {
        return view('insurance.index', [
            'categories' => InsuranceCategory::with('products')->orderBy('sort_order')->get(),
            'states' => config('cospark.us_state_names'),
        ]);
    }

    public function show(InsuranceProduct $product): View
    {
        abort_unless($product->is_active, 404);

        $category = $product->category;

        $related = InsuranceProduct::active()
            ->where('insurance_category_id', $category->id)
            ->whereKeyNot($product->id)
            ->get()
            ->concat(
                InsuranceProduct::active()
                    ->where('insurance_category_id', '!=', $category->id)
                    ->whereIn('slug', self::FEATURED_SLUGS)
                    ->get()
            )
            ->take(3);

        return view('insurance.show', [
            'product' => $product,
            'category' => $category,
            'related' => $related,
            'faqs' => $this->faqs($product->short_name),
            'quoteUrl' => in_array($product->slug, ['pet', 'device-protection'], true)
                ? route('contact', ['product' => $product->slug])
                : route('quote', ['type' => $product->quote_type ?: $category->quote_type]),
            'quoteCtaLabel' => in_array($product->slug, ['pet', 'device-protection'], true) ? 'Ask an agent for a quote' : 'Get a Quote',
        ]);
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function faqs(string $short): array
    {
        return [
            ["How much does {$short} coverage cost?", 'Pricing depends on your location, history, the coverage you choose and your deductible. Start a quote and an agent will compare options for you.'],
            ["Can I bundle {$short} with other policies?", 'Yes. Bundling two or more eligible policies usually lowers your total premium and keeps everything in one member dashboard.'],
            ['Do I earn COSPARK tokens on this policy?', "Associate members earn Insurance Tokens equal to 5% of eligible annual premium, and Premium members earn 7%. Free members don't earn tokens. If a policy is cancelled mid-year, that year's Insurance Tokens are forfeited."],
            ['How do I file a claim?', 'Report a claim online any time from the Claims page or your member dashboard. Logged-in members have their policy details filled in automatically.'],
        ];
    }
}
