<?php

namespace App\Services\Payments;

use App\Models\User;

/**
 * Card processing. Card data is never sent to or stored by COSPARK: the browser
 * hands the gateway's token (Authorize.net payment profile id) or, in test mode, only the
 * card's last four digits and expiry.
 */
interface PaymentGateway
{
    public function charge(User $user, float $amount, string $description, CardDetails $card): ChargeResult;

    public function name(): string;
}
