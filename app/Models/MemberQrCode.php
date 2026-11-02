<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A member's partner-redemption QR code; unique to the member and not transferable. */
class MemberQrCode extends Model
{
    protected $fillable = ['user_id', 'code', 'status'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
