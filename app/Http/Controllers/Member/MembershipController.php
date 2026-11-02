<?php

namespace App\Http\Controllers\Member;

use App\Services\MembershipService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** SOW: My Membership — billing frequency, plan changes and cancellation. */
class MembershipController extends MemberController
{
    public function __construct(private MembershipService $memberships) {}

    public function frequency(Request $request): JsonResponse
    {
        $user = $this->primary($request);
        $data = $request->validate(['frequency' => ['required', Rule::in(array_keys(config('membership.frequencies')))]]);

        $this->memberships->changeFrequency($user->membership, $data['frequency']);

        return response()->json(['ok' => true]);
    }

    /** Switching to a lower plan (or Free); upgrades go through PaymentController::upgrade. */
    public function switchPlan(Request $request): JsonResponse
    {
        $user = $this->primary($request);
        $data = $request->validate(['plan' => ['required', Rule::in(array_keys(config('membership.plans')))]]);
        $membership = $user->membership;
        abort_unless(config("membership.plans.{$data['plan']}.monthly_fee") < (float) $membership->monthly_fee, 422, 'Use upgrade for a higher plan.');

        $this->memberships->scheduleSwitch($membership, $data['plan']);

        return response()->json(['ok' => true, 'effective' => $membership->refresh()->pending_plan ? $membership->current_period_end?->toDateString() : today()->toDateString()]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = $this->primary($request);
        abort_unless($user->membership?->isPaid(), 422, 'Free memberships have nothing to cancel.');

        $this->memberships->cancel($user->membership);

        return response()->json(['ok' => true]);
    }
}
