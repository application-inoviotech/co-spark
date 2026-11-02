<?php

namespace App\Models;

use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** SOW: member-to-member token transfer, reviewed by COSPARK before completion. */
class TokenTransfer extends Model
{
    use HasReference;

    protected static string $referencePrefix = 'TRF';

    protected $fillable = ['reference', 'sender_id', 'recipient_id', 'token_type', 'quantity', 'note', 'status', 'indemnity_accepted_at'];

    protected $casts = ['indemnity_accepted_at' => 'datetime'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}
