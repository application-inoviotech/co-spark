<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Resource as Article;
use App\Models\ResourceCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** SOW: Resource management — create, edit, delete, categorise, publish / unpublish. */
class ArticleResource extends Resource
{
    public static string $model = Article::class;

    public static string $label = 'Resources & articles';

    public static string $singular = 'Article';

    public static string $group = 'Website content';

    public static array $search = ['title', 'summary', 'slug'];

    public static bool $creatable = true;

    public static bool $editable = true;

    public static bool $deletable = true;

    public function query(): Builder
    {
        return Article::with('category')->orderBy('sort_order');
    }

    public function columns(): array
    {
        return [
            ['key' => 'title', 'label' => 'Title'],
            ['key' => 'category.name', 'label' => 'Category'],
            ['key' => 'read_minutes', 'label' => 'Read (min)'],
            ['key' => 'is_published', 'label' => 'Published', 'type' => 'bool'],
        ];
    }

    public function filters(): array
    {
        return ['resource_category_id' => ['label' => 'Category', 'options' => ResourceCategory::orderBy('sort_order')->pluck('name', 'id')->all()]];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'title', 'label' => 'Title'],
            ['name' => 'slug', 'label' => 'URL slug', 'half' => true, 'hint' => 'Page address: /resources/{slug}'],
            ['name' => 'resource_category_id', 'label' => 'Category', 'type' => 'select', 'options' => ResourceCategory::orderBy('sort_order')->pluck('name', 'id')->all(), 'half' => true],
            ['name' => 'summary', 'label' => 'Summary', 'type' => 'textarea'],
            ['name' => 'image_url', 'label' => 'Image URL'],
            ['name' => 'body', 'label' => 'Article body (HTML)', 'type' => 'textarea', 'hint' => 'Add the approved article content before publishing.'],
            ['name' => 'read_minutes', 'label' => 'Reading time (minutes)', 'type' => 'number', 'half' => true],
            ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'half' => true],
            ['name' => 'is_published', 'label' => 'Published', 'type' => 'checkbox'],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'title' => ['required', 'string', 'max:160'], 'slug' => ['required', 'alpha_dash', 'max:80', Rule::unique('resources')->ignore($model?->id)],
            'resource_category_id' => ['required', Rule::exists('resource_categories', 'id')], 'summary' => ['required', 'string', 'max:500'],
            'image_url' => ['required', 'url', 'max:500'], 'body' => [Rule::requiredIf(request()->boolean('is_published')), 'nullable', 'string', 'max:60000', function ($attribute, $value, $fail) {
                if (request()->boolean('is_published') && preg_match('/^\s*$/u', html_entity_decode(strip_tags($value ?? '')))) {
                    $fail('Add approved article content before publishing.');
                }
            }],
            'read_minutes' => ['required', 'integer', 'between:1,60'], 'sort_order' => ['nullable', 'integer'], 'is_published' => ['boolean'],
        ];
    }
}
