<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Partner;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** SOW: Partner & Ecosystem Management — partners, eateries, auctions and vacation properties. */
class PartnerResource extends Resource
{
    public static string $model = Partner::class;

    public static string $label = 'Partners & properties';

    public static string $singular = 'Partner';

    public static string $group = 'Benefits';

    public static array $search = ['name', 'category', 'location'];

    public static bool $creatable = true;

    public static bool $editable = true;

    public static bool $deletable = true;

    public function query(): Builder
    {
        return Partner::orderBy('sort_order');
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'is_vacation', 'label' => 'Vacation property', 'type' => 'bool'],
            ['key' => 'location', 'label' => 'Location'],
            ['key' => 'is_active', 'label' => 'Live', 'type' => 'bool'],
        ];
    }

    public function filters(): array
    {
        return ['is_vacation' => ['label' => 'Kind', 'options' => ['1' => 'Vacation properties', '0' => 'Partners']]];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'half' => true],
            ['name' => 'category', 'label' => 'Category', 'half' => true, 'hint' => 'e.g. Vacations, Dining, Auctions'],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'image_url', 'label' => 'Image URL'],
            ['name' => 'website_url', 'label' => 'Website URL'],
            ['name' => 'is_vacation', 'label' => 'Vacation property (bookable with tokens)', 'type' => 'checkbox'],
            ['name' => 'location', 'label' => 'Location', 'half' => true],
            ['name' => 'nightly_rate', 'label' => 'Nightly rate ($)', 'type' => 'number', 'half' => true],
            ['name' => 'tokens_per_night', 'label' => 'DT credit per night', 'type' => 'number', 'half' => true],
            ['name' => 'amenities', 'label' => 'Amenities', 'type' => 'lines', 'hint' => 'One per line.'],
            ['name' => 'sort_order', 'label' => 'Sort order', 'type' => 'number', 'half' => true],
            ['name' => 'is_active', 'label' => 'Show on the website', 'type' => 'checkbox'],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:160'], 'category' => ['required', 'string', 'max:60'], 'description' => ['required', 'string', 'max:500'],
            'image_url' => ['required', 'url', 'max:500'], 'website_url' => ['nullable', 'url', 'max:500'], 'is_vacation' => ['boolean'],
            'location' => ['nullable', 'string', 'max:160'], 'nightly_rate' => ['nullable', 'numeric', 'min:0'], 'tokens_per_night' => ['nullable', 'integer', 'min:0'],
            'amenities' => ['nullable', 'string', 'max:1000'], 'sort_order' => ['nullable', 'integer'], 'is_active' => ['boolean'],
        ];
    }

    public function save(Model $partner, array $data): Model
    {
        $partner->fill($data + ['sort_order' => 0]);
        $partner->slug ??= Str::slug($data['name']).(Partner::where('slug', Str::slug($data['name']))->exists() ? '-'.Str::lower(Str::random(4)) : '');
        $partner->save();

        return $partner;
    }
}
