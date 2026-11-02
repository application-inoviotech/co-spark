<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PolicyDocument extends Model
{
    protected $fillable = ['policy_id', 'name', 'path'];

    public function policy(): BelongsTo
    {
        return $this->belongsTo(Policy::class);
    }
}
