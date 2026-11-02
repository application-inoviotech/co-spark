<?php

namespace App\Services\Payments;

/** What the server is allowed to know about a card: a gateway token or display details. */
final class CardDetails
{
    public function __construct(
        public readonly string $holder,
        public readonly string $last4,
        public readonly string $brand,
        public readonly ?string $token = null,
    ) {}

    public function label(): string
    {
        return "{$this->brand} •••• {$this->last4}";
    }
}
