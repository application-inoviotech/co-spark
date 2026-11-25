<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InsuranceProduct extends Model
{
    protected $fillable = ['insurance_category_id', 'slug', 'name', 'image_url', 'tagline', 'intro', 'quote_type', 'sort_order', 'is_active', 'content'];

    protected $casts = ['is_active' => 'boolean', 'content' => 'array'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(InsuranceCategory::class, 'insurance_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }

    /** Sentence-friendly product name, preserving the ATV and RV initialisms. */
    public function getShortNameAttribute(): string
    {
        return preg_replace_callback('/\b(atv|rv)\b/i', fn ($match) => strtoupper($match[0]), Str::lower(str_replace(' Insurance', '', $this->name)));
    }

    /** Images are stored at 1600px; cards use a smaller rendition. */
    public function imageAt(int $width): string
    {
        return str_replace('w=1600', "w={$width}", $this->image_url);
    }
}
