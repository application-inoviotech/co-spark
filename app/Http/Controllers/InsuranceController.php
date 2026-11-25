<?php

namespace App\Http\Controllers;

use App\Models\InsuranceCategory;
use App\Models\InsuranceProduct;
use App\Support\InsuranceContent;
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
            'copy' => app(InsuranceContent::class)->forProduct($product),
            'quoteUrl' => in_array($product->slug, ['pet', 'device-protection', 'financial-solutions', 'asset-management'], true)
                ? route('contact', ['product' => $product->slug])
                : route('quote', ['type' => $product->quote_type ?: $category->quote_type]),
            'quoteCtaLabel' => $category->slug === 'financial' ? 'Discuss your goals' : (in_array($product->slug, ['pet', 'device-protection'], true) ? 'Ask an agent for a quote' : 'Get a Quote'),
        ]);
    }
}
