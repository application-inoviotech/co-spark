<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\ResourceCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** CMS: resource categories (filter chips on /resources). */
class ArticleCategoryResource extends Resource
{
    public static string $model = ResourceCategory::class;

    public static string $label = 'Resource categories';

    public static string $singular = 'Resource category';

    public static string $group = 'Website content';

    public static bool $creatable = true;

    public static bool $editable = true;

    public function query(): Builder
    {
        return ResourceCategory::withCount('resources')->orderBy('sort_order');
    }

    public function columns(): array
    {
        return [['key' => 'name', 'label' => 'Name'], ['key' => 'slug', 'label' => 'Slug'], ['key' => 'resources_count', 'label' => 'Articles'], ['key' => 'show_in_filter', 'label' => 'Shown as filter', 'type' => 'bool']];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'half' => true],
            ['name' => 'slug', 'label' => 'Slug', 'half' => true, 'hint' => 'Used in /resources?cat={slug}'],
            ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'half' => true],
            ['name' => 'show_in_filter', 'label' => 'Show as a filter chip', 'type' => 'checkbox'],
        ];
    }

    public function rules(?Model $model): array
    {
        return ['name' => ['required', 'string', 'max:80'], 'slug' => ['required', 'alpha_dash', 'max:40', Rule::unique('resource_categories')->ignore($model?->id)], 'sort_order' => ['nullable', 'integer'], 'show_in_filter' => ['boolean']];
    }
}
