<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/** A partly completed quote the visitor saved to finish later (SOW: Retrieve a Saved Quote). */
class QuoteDraft extends Model
{
    public const DAYS_VALID = 30;

    protected $fillable = ['code', 'email', 'state', 'step', 'expires_at'];

    protected $casts = ['state' => 'array', 'expires_at' => 'datetime'];

    protected static function booted(): void
    {
        static::creating(function (QuoteDraft $draft) {
            do {
                $draft->code = Str::upper(Str::random(8));
            } while (self::where('code', $draft->code)->exists());
            $draft->expires_at ??= now()->addDays(self::DAYS_VALID);
        });
    }

    public static function findValid(string $code, ?string $email = null): ?self
    {
        return self::where('code', strtoupper(trim($code)))
            ->when($email, fn ($q) => $q->where('email', strtolower(trim($email))))
            ->where('expires_at', '>', now())
            ->first();
    }
}
