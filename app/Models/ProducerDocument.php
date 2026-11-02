<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProducerDocument extends Model
{
    public const STATUS_LABELS = ['needed' => 'Needed', 'pending_review' => 'Pending review', 'verified' => 'Verified', 'rejected' => 'Rejected'];

    protected $fillable = ['producer_id', 'name', 'path', 'status', 'uploaded_at'];

    protected $casts = ['uploaded_at' => 'datetime'];

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }
}
