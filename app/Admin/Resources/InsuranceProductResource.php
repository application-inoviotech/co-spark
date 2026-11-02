<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\InsuranceCategory;
use App\Models\InsuranceProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** CMS: insurance product detail pages (Category → Sub-category → Product). */
class InsuranceProductResource extends Resource
{
    public static string $model = InsuranceProduct::class;

    public static string $label = 'Insurance products';

    public static string $singular = 'Insurance product';

    public static string $group = 'Website content';

    public static array $search = ['name', 'slug', 'tagline'];

    public static bool $creatable = true;

    public static bool $editable = true;

    public static bool $deletable = true;

    public function query(): Builder
    {
        return InsuranceProduct::with('category')->orderBy('sort_order');
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Product'],
            ['key' => 'category.name', 'label' => 'Category'],
            ['key' => 'slug', 'label' => 'URL slug'],
            ['key' => 'quote_type', 'label' => 'Quote form', 'type' => 'pill'],
            ['key' => 'is_active', 'label' => 'Live', 'type' => 'bool'],
        ];
    }

    public function filters(): array
    {
        return ['insurance_category_id' => ['label' => 'Category', 'options' => InsuranceCategory::orderBy('sort_order')->pluck('name', 'id')->all()]];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'half' => true],
            ['name' => 'slug', 'label' => 'URL slug', 'half' => true, 'hint' => 'Page address: /insurance/{slug}'],
            ['name' => 'insurance_category_id', 'label' => 'Category', 'type' => 'select', 'options' => InsuranceCategory::orderBy('sort_order')->pluck('name', 'id')->all(), 'half' => true],
            ['name' => 'quote_type', 'label' => 'Quote form', 'type' => 'select', 'options' => config('cospark.quote_types'), 'half' => true],
            ['name' => 'tagline', 'label' => 'Hero headline'],
            ['name' => 'intro', 'label' => 'Introduction', 'type' => 'textarea'],
            ['name' => 'image_url', 'label' => 'Hero image URL'],
            ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'half' => true],
            ['name' => 'is_active', 'label' => 'Published', 'type' => 'checkbox'],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:120'], 'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique('insurance_products')->ignore($model?->id)],
            'insurance_category_id' => ['required', Rule::exists('insurance_categories', 'id')], 'quote_type' => ['required', Rule::in(array_keys(config('cospark.quote_types')))],
            'tagline' => ['required', 'string', 'max:160'], 'intro' => ['required', 'string', 'max:1000'], 'image_url' => ['required', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer'], 'is_active' => ['boolean'],
        ];
    }
}
