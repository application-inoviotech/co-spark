<?php

namespace App\Models;

use App\Models\Concerns\HasReference;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasReference;

    public const TYPES = ['phone' => 'Phone call', 'in_person' => 'In person'];

    protected static string $referencePrefix = 'APT';

    protected $fillable = ['reference', 'type', 'preferred_date', 'preferred_time', 'name', 'email', 'phone', 'reason', 'message', 'status', 'user_id', 'producer_id'];

    protected $casts = ['preferred_date' => 'date'];

    public function producer(): BelongsTo
    {
        return $this->belongsTo(Producer::class);
    }

    /** The member who booked it while signed in (null for website visitors). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }
}
