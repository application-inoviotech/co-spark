<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Page;
use App\Support\Content;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** CMS: website copy, SEO meta and menus; fields come from resources/content/{slug}.php. */
class PageResource extends Resource
{
    public static string $model = Page::class;

    public static string $label = 'Pages';

    public static string $singular = 'Page';

    public static string $group = 'Website content';

    public static bool $editable = true;

    public function query(): Builder
    {
        return Page::orderBy('id');
    }

    public function columns(): array
    {
        return [['key' => 'name', 'label' => 'Page'], ['key' => 'slug', 'label' => 'Slug'], ['key' => 'updated_at', 'label' => 'Last edited', 'type' => 'datetime']];
    }

    public function fields(?Model $model): array
    {
        return collect(Content::definition($model->slug)['fields'])->map(fn ($f, $key) => [
            'name' => $key,
            'label' => $f[0],
            'type' => $f[1],
            'hint' => $f[3] ?? ($f[1] === 'lines' ? 'One item per line.' : null),
        ])->values()->all();
    }

    public function value(Model $model, string $field): mixed
    {
        return $model->content[$field] ?? Content::defaults($model->slug)[$field] ?? null;
    }

    public function rules(?Model $model): array
    {
        return collect(Content::definition($model->slug)['fields'])
            ->map(fn ($f) => $f[1] === 'text' ? ['required', 'string', 'max:255'] : ['nullable', 'string', 'max:10000'])
            ->all();
    }

    public function save(Model $model, array $data): Model
    {
        $model->content = array_merge($model->content ?? [], $data);
        $model->save();

        return $model;
    }
}
