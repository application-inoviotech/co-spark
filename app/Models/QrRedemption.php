<?php

namespace App\Models;

use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrRedemption extends Model
{
    use HasReference;

    protected static string $referencePrefix = 'RDM';

    protected $fillable = ['reference', 'user_id', 'partner_id', 'location', 'token_type', 'amount'];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
