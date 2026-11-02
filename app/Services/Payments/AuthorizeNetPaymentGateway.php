<?php

namespace App\Services\Payments;

use App\Models\User;
use App\Services\Billing\AuthorizeNet;
use App\Services\Billing\BillingException;
use App\Services\Billing\PaymentDeclined;

/** One-time charges (insurance premiums, Vacation Tokens) on a saved Authorize.net card. */
class AuthorizeNetPaymentGateway implements PaymentGateway
{
    public function __construct(private AuthorizeNet $authorizeNet) {}

    public function charge(User $user, float $amount, string $description, CardDetails $card): ChargeResult
    {
        $method = $card->token ? $user->paymentMethods()->where('gateway_token', $card->token)->first() : null;
        if (! $method) {
            return ChargeResult::failure('Add the card again to use it for payments.');
        }

        try {
            return ChargeResult::success($this->authorizeNet->charge($method, $amount, $description));
        } catch (PaymentDeclined $e) {
            return ChargeResult::failure($e->getMessage());
        } catch (BillingException $e) {
            report($e);

            return ChargeResult::failure('The payment could not be processed. Please try again.');
        }
    }

    public function name(): string
    {
        return 'authorizenet';
    }
}
