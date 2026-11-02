<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceCategory extends Model
{
    protected $fillable = ['slug', 'name', 'lead', 'quote_type', 'coverage', 'options', 'benefits', 'sort_order'];

    protected $casts = [
        'coverage' => 'array',
        'options' => 'array',
        'benefits' => 'array',
    ];

    /** Coverage as "Title | Description" lines for the admin form. */
    public function getCoverageLinesAttribute(): array
    {
        return array_map(fn ($c) => "{$c['title']} | {$c['text']}", $this->coverage ?? []);
    }

    public function products(): HasMany
    {
        return $this->hasMany(InsuranceProduct::class)->where('is_active', true)->orderBy('sort_order');
    }
}
