<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Shared checks for member dashboard endpoints. */
abstract class MemberController extends Controller
{
    /** Sub-accounts can't manage billing, policies, claims, wallet or other sub-accounts. */
    protected function primary(Request $request): User
    {
        $user = $request->user();
        abort_if($user->isSubAccount(), 403, 'This is managed by the main Premium member.');

        return $user;
    }

    /** Token, QR and vacation features need an active Associate or Premium membership. */
    protected function paidMember(Request $request): User
    {
        $user = $this->primary($request);
        $membership = $user->membership;
        abort_unless($membership?->isPaid() && $membership->status === 'active', 403, 'This is part of paid membership. Upgrade to Associate or Premium.');

        return $user;
    }

    protected function ownedMethod(User $user, int|string|null $id): PaymentMethod
    {
        return $user->paymentMethods()->findOrFail($id);
    }

    protected function paymentResult(Payment $payment, array $extra = []): JsonResponse
    {
        return response()->json([
            'status' => $payment->status,
            'reference' => $payment->reference,
            'failure' => $payment->failure_reason,
            ...$extra,
        ]);
    }
}
