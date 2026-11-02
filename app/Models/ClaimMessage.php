<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaimMessage extends Model
{
    protected $fillable = ['claim_id', 'user_id', 'author_name', 'from_member', 'body'];

    protected $casts = ['from_member' => 'boolean'];

    public function claim(): BelongsTo
    {
        return $this->belongsTo(Claim::class);
    }
}
