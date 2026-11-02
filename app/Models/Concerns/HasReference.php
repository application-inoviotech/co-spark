<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

/**
 * Generates a unique public reference such as "QT-26K4X9QZ" on create.
 * The using model defines `protected static string $referencePrefix`.
 */
trait HasReference
{
    protected static function bootHasReference(): void
    {
        static::creating(function ($model) {
            $model->reference ??= static::newReference();
        });
    }

    public static function newReference(): string
    {
        do {
            $reference = sprintf('%s-%s%s', static::$referencePrefix, now()->format('y'), Str::upper(Str::random(6)));
        } while (static::where('reference', $reference)->exists());

        return $reference;
    }
}
