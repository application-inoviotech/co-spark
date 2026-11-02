<?php

namespace App\Http\Controllers\Member;

use App\Models\Payment;
use App\Services\MemberBillingService;
use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** SOW: Membership Dues, Insurance Payments, Vacation Token Purchase, Payment Confirmation. */
class PaymentController extends MemberController
{
    public function __construct(
        private MembershipService $memberships,
        private MemberBillingService $billing,
    ) {}

    public function dues(Request $request): JsonResponse
    {
        $user = $this->primary($request);
        $data = $request->validate(['method_id' => ['required', 'integer']]);
        $membership = $user->membership;
        abort_unless($membership?->isPaid(), 422, 'Your plan has no membership dues.');

        $method = $this->ownedMethod($user, $data['method_id']);
        $payment = $this->memberships->payDues($membership, $membership->billing_frequency, $method);

        return $this->paymentResult($payment, ['tokens' => $payment->succeeded() ? $membership->periodDuesTokens() : 0]);
    }

    public function premium(Request $request): JsonResponse
    {
        $user = $this->primary($request);
        $data = $request->validate(['method_id' => ['required', 'integer'], 'policy_id' => ['required', 'integer']]);
        $policy = $user->policies()->findOrFail($data['policy_id']);

        $payment = $this->billing->payPremium($user, $policy, $this->ownedMethod($user, $data['method_id']));

        return $this->paymentResult($payment, ['tokens' => $payment->succeeded() ? $this->billing->insuranceTokensFor($user, $policy) : 0]);
    }

    public function vacationTokens(Request $request): JsonResponse
    {
        $user = $this->paidMember($request);
        $data = $request->validate([
            'method_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'min:'.config('membership.vacation_token_minimum'), 'max:50000'],
        ]);

        $payment = $this->billing->buyVacationTokens($user, $data['quantity'], $this->ownedMethod($user, $data['method_id']));

        return $this->paymentResult($payment, ['tokens' => $payment->succeeded() ? $data['quantity'] : 0]);
    }

    public function upgrade(Request $request): JsonResponse
    {
        $user = $this->primary($request);
        $data = $request->validate([
            'method_id' => ['required', 'integer'],
            'plan' => ['required', Rule::in(['associate', 'premium'])],
        ]);
        $membership = $user->membership;
        $newFee = config("membership.plans.{$data['plan']}.monthly_fee");
        // An expired / cancelled membership counts as Free, so any paid plan renews it
        $currentFee = $membership->status === 'active' ? (float) $membership->monthly_fee : 0;
        abort_unless($newFee > $currentFee, 422, 'Choose a higher plan to upgrade.');

        $payment = $this->memberships->upgrade($membership, $data['plan'], $this->ownedMethod($user, $data['method_id']));

        return $this->paymentResult($payment, ['tokens' => $payment->succeeded() ? config("membership.plans.{$data['plan']}.dues_tokens") : 0]);
    }

    public function retry(Request $request, Payment $payment): JsonResponse
    {
        $user = $this->primary($request);
        abort_unless($payment->user_id === $user->id && $payment->status === 'failed', 404);
        $data = $request->validate(['method_id' => ['required', 'integer']]);

        return $this->paymentResult($this->billing->retry($user, $payment, $this->ownedMethod($user, $data['method_id'])));
    }

    /** Printable receipt / invoice for a payment. */
    public function receipt(Request $request, Payment $payment): View
    {
        abort_unless($payment->user_id === $request->user()->id || $request->user()->isAdmin(), 404);

        return view('dashboard.receipt', ['payment' => $payment, 'user' => $payment->user, 'office' => config('cospark.office')]);
    }
}
