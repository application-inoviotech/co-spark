<?php

namespace App\Services\Billing;

use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;

/**
 * Authorize.net API for COSPARK billing: one customer profile per member (CIM) holding their
 * saved cards, charges against a saved card, and refunds. Card data never reaches COSPARK: the
 * browser tokenizes it with Accept.js and only the single-use opaque token is posted.
 * Renewals are charged by the daily billing job (see MembershipService::renew()).
 *
 * The JSON API is converted to XML by Authorize.net, so keys must stay in schema order.
 */
class AuthorizeNet
{
    /** Values the browser needs for Accept.js. */
    public function clientConfig(): array
    {
        return [
            'loginId' => config('services.authorizenet.login_id'),
            'clientKey' => config('services.authorizenet.client_key'),
            'script' => config('services.authorizenet.sandbox') ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js',
        ];
    }

    public function customerProfileId(User $user): string
    {
        if ($user->gateway_customer_id) {
            return $user->gateway_customer_id;
        }

        $data = $this->request('createCustomerProfileRequest', [
            'profile' => ['merchantCustomerId' => (string) $user->id, 'description' => mb_substr($user->name, 0, 255), 'email' => $user->email],
        ]);
        $id = $data['customerProfileId'] ?? null;
        // E00039: Authorize.net already holds a profile for this member; reuse it
        if (! $id && $this->code($data) === 'E00039' && preg_match('/ID (\d+)/', $this->message($data), $m)) {
            $id = $m[1];
        }
        if (! $id) {
            throw new BillingException('Could not create the billing profile: '.$this->message($data));
        }
        $user->forceFill(['gateway_customer_id' => $id])->save();

        return $id;
    }

    /** Save a card tokenized by Accept.js (the token is single-use and expires after 15 minutes). */
    public function savePaymentMethod(User $user, string $descriptor, string $token, bool $makeDefault = false, string $field = 'payment_method'): PaymentMethod
    {
        $profileId = $this->customerProfileId($user);
        $data = $this->request('createCustomerPaymentProfileRequest', [
            'customerProfileId' => $profileId,
            'paymentProfile' => [
                // The member's address on file is the billing address (the account's AVS check needs street and ZIP)
                'billTo' => [
                    'firstName' => mb_substr((string) $user->first_name, 0, 50), 'lastName' => mb_substr((string) $user->last_name, 0, 50),
                    'address' => mb_substr(trim($user->street.' '.$user->street2), 0, 60), 'city' => mb_substr((string) $user->city, 0, 40),
                    'state' => mb_substr((string) $user->state, 0, 40), 'zip' => mb_substr((string) $user->zip, 0, 20), 'country' => 'US',
                ],
                'payment' => ['opaqueData' => ['dataDescriptor' => $descriptor, 'dataValue' => $token]],
            ],
            'validationMode' => 'liveMode', // a $0 authorization proves the card works before it is saved
        ]);

        // E00039: the same card is already saved; use the existing payment profile
        $paymentProfileId = $data['customerPaymentProfileId'] ?? null;
        if (! $paymentProfileId || ($this->code($data) !== 'I00001' && $this->code($data) !== 'E00039')) {
            throw ValidationException::withMessages([$field => $this->declineMessage($data['validationDirectResponse'] ?? null, $this->message($data))]);
        }
        if ($existing = $user->paymentMethods()->where('gateway_token', $paymentProfileId)->first()) {
            return $existing;
        }

        $card = $this->request('getCustomerPaymentProfileRequest', [
            'customerProfileId' => $profileId, 'customerPaymentProfileId' => $paymentProfileId, 'unmaskExpirationDate' => true,
        ])['paymentProfile']['payment']['creditCard'] ?? [];
        [$year, $month] = array_pad(explode('-', (string) ($card['expirationDate'] ?? '')), 2, null);

        $makeDefault = $makeDefault || ! $user->paymentMethods()->exists();
        $method = $user->paymentMethods()->create([
            'type' => 'card',
            'brand' => $card['cardType'] ?? 'Card',
            'last4' => substr((string) ($card['cardNumber'] ?? ''), -4),
            'exp_month' => $month ? (int) $month : null,
            'exp_year' => $year ? (int) $year : null,
            'gateway_token' => $paymentProfileId,
            'is_default' => false,
        ]);
        if ($makeDefault) {
            $this->makeDefault($user, $method);
        }

        return $method->refresh();
    }

    /** The default card is used for dues renewals and sub-account fees. */
    public function makeDefault(User $user, PaymentMethod $method): void
    {
        $user->paymentMethods()->update(['is_default' => false]);
        $method->update(['is_default' => true]);
    }

    public function detach(PaymentMethod $method): void
    {
        if ($method->gateway_token && $method->user->gateway_customer_id) {
            $this->request('deleteCustomerPaymentProfileRequest', [
                'customerProfileId' => $method->user->gateway_customer_id, 'customerPaymentProfileId' => $method->gateway_token,
            ]);
        }
        $method->delete();
    }

    /**
     * Charge a saved card and return the transaction id.
     *
     * @throws PaymentDeclined when the card is declined or the payment is held
     */
    public function charge(PaymentMethod $method, float $amount, string $description, ?string $invoice = null): string
    {
        if (! $method->gateway_token) {
            throw new PaymentDeclined('Add the card again to use it for payments.');
        }

        $data = $this->request('createTransactionRequest', ['transactionRequest' => [
            'transactionType' => 'authCaptureTransaction',
            'amount' => number_format($amount, 2, '.', ''),
            'profile' => [
                'customerProfileId' => $this->customerProfileId($method->user),
                'paymentProfile' => ['paymentProfileId' => $method->gateway_token],
            ],
            'order' => ['invoiceNumber' => mb_substr((string) $invoice, 0, 20), 'description' => mb_substr($description, 0, 255)],
        ]]);

        $result = $data['transactionResponse'] ?? [];
        if (($result['responseCode'] ?? null) === '1' && ! empty($result['transId'])) {
            return $result['transId'];
        }

        throw new PaymentDeclined($this->declineMessage(null, $result['errors'][0]['errorText'] ?? $this->message($data)));
    }

    /** Server-side status verification for the webhook reconciler. */
    public function transactionDetails(string $transactionId): array
    {
        $data = $this->request('getTransactionDetailsRequest', ['transId' => $transactionId]);
        if (($data['messages']['resultCode'] ?? null) !== 'Ok' || empty($data['transaction'])) {
            throw new BillingException('Transaction details are not available at Authorize.net.');
        }

        return $data['transaction'];
    }

    /** Full refund: unsettled transactions are voided, settled ones refunded to the same card. */
    public function refund(string $transactionId): void
    {
        $transaction = $this->request('getTransactionDetailsRequest', ['transId' => $transactionId])['transaction']
            ?? throw new BillingException('Transaction not found at Authorize.net.');

        if (($transaction['transactionStatus'] ?? '') === 'voided') {
            return;
        }

        $unsettled = in_array($transaction['transactionStatus'] ?? '', ['authorizedPendingCapture', 'capturedPendingSettlement'], true);
        $request = $unsettled
            ? ['transactionType' => 'voidTransaction', 'refTransId' => $transactionId]
            : [
                'transactionType' => 'refundTransaction',
                'amount' => number_format((float) $transaction['settleAmount'], 2, '.', ''),
                'payment' => ['creditCard' => ['cardNumber' => substr((string) ($transaction['payment']['creditCard']['cardNumber'] ?? ''), -4), 'expirationDate' => 'XXXX']],
                'refTransId' => $transactionId,
            ];

        $data = $this->request('createTransactionRequest', ['transactionRequest' => $request]);
        if (($data['transactionResponse']['responseCode'] ?? null) !== '1') {
            throw new BillingException('Refund failed: '.($data['transactionResponse']['errors'][0]['errorText'] ?? $this->message($data)));
        }
    }

    private function request(string $type, array $body): array
    {
        $auth = ['merchantAuthentication' => ['name' => config('services.authorizenet.login_id'), 'transactionKey' => config('services.authorizenet.transaction_key')]];
        $endpoint = config('services.authorizenet.sandbox') ? 'https://apitest.authorize.net/xml/v1/request.api' : 'https://api.authorize.net/xml/v1/request.api';

        try {
            $response = Http::timeout(45)->withBody(json_encode([$type => $auth + $body]), 'application/json')->post($endpoint);
        } catch (\Throwable $e) {
            throw new BillingException('Authorize.net could not be reached: '.$e->getMessage(), previous: $e);
        }
        // Responses start with a UTF-8 byte-order mark
        $data = json_decode(preg_replace('/^\xEF\xBB\xBF/', '', $response->body()), true);

        return is_array($data) ? $data : throw new BillingException('Unexpected response from Authorize.net.');
    }

    private function code(array $data): ?string
    {
        return $data['messages']['message'][0]['code'] ?? null;
    }

    private function message(array $data): string
    {
        return $data['messages']['message'][0]['text'] ?? 'The payment could not be processed.';
    }

    /** Member-facing wording for a declined card (the gateway's direct response carries the bank's reason). */
    private function declineMessage(?string $directResponse, string $fallback): string
    {
        $reason = $directResponse ? (explode(',', $directResponse)[3] ?? $fallback) : $fallback;

        return rtrim(trim($reason), '.').'. Check the card details or try another card.';
    }
}
