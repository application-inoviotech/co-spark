<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Website CMS page: editable copy stored as key => value (see App\Support\Content). */
class Page extends Model
{
    protected $fillable = ['slug', 'name', 'content'];

    protected $casts = ['content' => 'array'];

    protected static function booted(): void
    {
        static::saved(fn (self $page) => Cache::forget("cms.{$page->slug}"));
    }
}
