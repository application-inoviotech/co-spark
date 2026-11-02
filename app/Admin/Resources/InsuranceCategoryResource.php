<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\InsuranceCategory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** CMS: category coverage overview, options and member benefits shown on every product page. */
class InsuranceCategoryResource extends Resource
{
    public static string $model = InsuranceCategory::class;

    public static string $label = 'Insurance categories';

    public static string $singular = 'Insurance category';

    public static string $group = 'Website content';

    public static bool $editable = true;

    public function query(): Builder
    {
        return InsuranceCategory::orderBy('sort_order');
    }

    public function columns(): array
    {
        return [['key' => 'name', 'label' => 'Category'], ['key' => 'lead', 'label' => 'Intro'], ['key' => 'quote_type', 'label' => 'Quote form', 'type' => 'pill']];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'half' => true],
            ['name' => 'quote_type', 'label' => 'Default quote form', 'type' => 'select', 'options' => config('cospark.quote_types'), 'half' => true],
            ['name' => 'lead', 'label' => 'Intro line'],
            ['name' => 'coverage_lines', 'label' => 'What\'s covered', 'type' => 'lines', 'hint' => 'One per line: Title | Description'],
            ['name' => 'options', 'label' => 'Coverage options', 'type' => 'lines'],
            ['name' => 'benefits', 'label' => 'Member benefits', 'type' => 'lines'],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:80'], 'quote_type' => ['required', Rule::in(array_keys(config('cospark.quote_types')))],
            'lead' => ['nullable', 'string', 'max:255'], 'coverage_lines' => ['required', 'string'], 'options' => ['required', 'string'], 'benefits' => ['required', 'string'],
        ];
    }

    public function save(Model $category, array $data): Model
    {
        $data['coverage'] = array_map(function ($line) {
            [$title, $text] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');

            return ['title' => $title, 'text' => $text];
        }, $data['coverage_lines']);
        $category->fill(collect($data)->except('coverage_lines')->all())->save();

        return $category;
    }
}
