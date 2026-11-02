<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class InsuranceProduct extends Model
{
    protected $fillable = ['insurance_category_id', 'slug', 'name', 'image_url', 'tagline', 'intro', 'quote_type', 'sort_order', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

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

    /** Product name without the trailing " Insurance", lower-cased for copy ("car", "homeowners"). */
    public function getShortNameAttribute(): string
    {
        return Str::lower(str_replace(' Insurance', '', $this->name));
    }

    /** Images are stored at 1600px; cards use a smaller rendition. */
    public function imageAt(int $width): string
    {
        return str_replace('w=1600', "w={$width}", $this->image_url);
    }
}
