<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ResourceCategory extends Model
{
    protected $fillable = ['slug', 'name', 'show_in_filter', 'sort_order'];

    protected $casts = ['show_in_filter' => 'boolean'];

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }
}
