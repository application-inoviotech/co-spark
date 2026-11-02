<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\ClaimType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** CMS: claim-type guidance on the Claims page (actions, steps, documents, photos). */
class ClaimTypeResource extends Resource
{
    public static string $model = ClaimType::class;

    public static string $label = 'Claim types';

    public static string $singular = 'Claim type';

    public static string $group = 'Website content';

    public static bool $editable = true;

    public function query(): Builder
    {
        return ClaimType::orderBy('sort_order');
    }

    public function columns(): array
    {
        return [['key' => 'name', 'label' => 'Claim type'], ['key' => 'slug', 'label' => 'Slug']];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Name'],
            ['name' => 'immediate_actions', 'label' => 'Immediate actions', 'type' => 'lines'],
            ['name' => 'steps', 'label' => 'Claim steps', 'type' => 'lines'],
            ['name' => 'documents', 'label' => 'Required documents', 'type' => 'lines'],
            ['name' => 'photo_guidance', 'label' => 'Photo & evidence guidance', 'type' => 'textarea'],
        ];
    }

    public function rules(?Model $model): array
    {
        return ['name' => ['required', 'string', 'max:60'], 'immediate_actions' => ['required', 'string'], 'steps' => ['required', 'string'], 'documents' => ['required', 'string'], 'photo_guidance' => ['required', 'string', 'max:1000']];
    }
}
