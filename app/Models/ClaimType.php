<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClaimType extends Model
{
    protected $fillable = ['slug', 'name', 'immediate_actions', 'steps', 'documents', 'photo_guidance', 'sort_order'];

    protected $casts = [
        'immediate_actions' => 'array',
        'steps' => 'array',
        'documents' => 'array',
    ];
}
