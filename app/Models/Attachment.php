<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = ['field', 'original_name', 'path', 'mime', 'size'];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }
}
