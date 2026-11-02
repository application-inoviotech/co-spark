<?php

namespace App\Http\Controllers\Member;

use App\Models\PaymentMethod;
use App\Services\Billing\AuthorizeNet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * SOW: Manage Payment Methods. Cards are tokenized in the browser by Accept.js and saved in the
 * member's Authorize.net profile; COSPARK keeps only the payment profile id and display details
 * (brand, last four digits, expiry).
 */
class PaymentMethodController extends MemberController
{
    public function __construct(private AuthorizeNet $authorizeNet) {}

    public function store(Request $request): JsonResponse
    {
        $user = $this->primary($request);
        $data = $request->validate([
            'payment_method' => ['required', 'string', 'max:8192'], // Accept.js opaque token
            'payment_descriptor' => ['required', 'string', 'in:COMMON.ACCEPT.INAPP.PAYMENT'],
            'default' => ['boolean'],
        ]);

        $method = $this->authorizeNet->savePaymentMethod($user, $data['payment_descriptor'], $data['payment_method'], (bool) ($data['default'] ?? false));

        return response()->json(['id' => $method->id], 201);
    }

    public function makeDefault(Request $request, PaymentMethod $method): JsonResponse
    {
        $user = $this->primary($request);
        abort_unless($method->user_id === $user->id, 404);
        abort_if($method->isExpired(), 422, 'This card has expired.');

        $this->authorizeNet->makeDefault($user, $method);

        return response()->json(['ok' => true]);
    }

    public function destroy(Request $request, PaymentMethod $method): JsonResponse
    {
        $user = $this->primary($request);
        abort_unless($method->user_id === $user->id, 404);
        abort_if($method->is_default && $user->membership?->auto_renew && $user->membership->isPaid(), 422,
            'This card pays your recurring dues. Make another method the default first.');

        $this->authorizeNet->detach($method);

        return response()->json(['ok' => true]);
    }
}
