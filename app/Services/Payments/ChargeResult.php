<?php

namespace App\Services\Payments;

final class ChargeResult
{
    private function __construct(
        public readonly bool $successful,
        public readonly ?string $reference,
        public readonly ?string $failureReason,
    ) {}

    public static function success(string $reference): self
    {
        return new self(true, $reference, null);
    }

    public static function failure(string $reason): self
    {
        return new self(false, null, $reason);
    }
}
