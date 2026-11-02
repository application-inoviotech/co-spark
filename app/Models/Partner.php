<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A COSPARK ecosystem partner or vacation property. */
class Partner extends Model
{
    protected $fillable = ['slug', 'name', 'category', 'description', 'image_url', 'is_vacation', 'location', 'amenities', 'nightly_rate', 'tokens_per_night', 'website_url', 'sort_order', 'is_active'];

    protected $casts = [
        'amenities' => 'array',
        'is_vacation' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    public function imageAt(int $width): string
    {
        return str_replace('w=1600', "w={$width}", $this->image_url);
    }
}
