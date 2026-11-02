<?php

namespace App\Http\Controllers\Member;

use App\Models\OneTimeCode;
use App\Models\User;
use App\Notifications\SubAccountInvitation;
use App\Services\MembershipService;
use App\Services\OneTimeCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * SOW: Sub-accounts — Premium members add family, employees or group members ($8.88/month each).
 * Each sub-account gets its own login (set via an emailed code) and limited permissions.
 */
class SubAccountController extends MemberController
{
    public function __construct(private MembershipService $memberships) {}

    /** Sub-accounts that are billed: invited or active (not deactivated). */
    public function store(Request $request, OneTimeCodeService $codes): JsonResponse
    {
        $owner = $this->primary($request);
        abort_unless($owner->membership?->plan === 'premium' && $owner->membership->status === 'active', 403, 'Sub-accounts are a Premium feature.');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'relationship' => ['required', Rule::in(['Spouse / partner', 'Child (18+)', 'Family member', 'Employee', 'Group member'])],
            'email' => ['required', 'email:filter', 'max:255', Rule::unique('users', 'email')],
            'permissions' => ['array'],
            'permissions.*' => [Rule::in(config('membership.sub_account_permissions'))],
        ], ['email.unique' => 'This email already has a COSPARK account.']);

        [$first, $last] = array_pad(explode(' ', trim($data['name']), 2), 2, '');

        $sub = DB::transaction(function () use ($owner, $data, $first, $last) {
            $sub = User::create([
                'first_name' => $first,
                'last_name' => $last,
                'email' => strtolower($data['email']),
                'role' => User::ROLE_MEMBER,
                'status' => 'pending', // until they set a password
                'parent_id' => $owner->id,
                'relationship' => $data['relationship'],
                'permissions' => $data['permissions'] ?? [],
                'password' => Str::random(40),
            ]);
            $sub->memberships()->create([
                'plan' => 'sub_account',
                'monthly_fee' => config('membership.sub_account_fee'),
                'status' => 'active',
                'started_at' => now(),
            ]);

            // $8.88/month per sub-account on the owner's card; a declined charge rolls the sub-account back
            $this->memberships->syncSubAccounts($owner);

            return $sub;
        });

        $codes->issue($sub->email, OneTimeCode::RESET_PASSWORD);
        $sub->notify(new SubAccountInvitation($owner->name));

        return response()->json(['id' => $sub->id], 201);
    }

    public function setStatus(Request $request, User $sub): JsonResponse
    {
        $owner = $this->primary($request);
        abort_unless($sub->parent_id === $owner->id, 404);
        $data = $request->validate(['active' => ['required', 'boolean']]);

        $sub->update(['status' => $data['active'] ? ($sub->email_verified_at ? 'active' : 'pending') : 'suspended']);
        $sub->membership?->update(['status' => $data['active'] ? 'active' : 'cancelled']);
        $this->memberships->syncSubAccounts($owner);

        return response()->json(['ok' => true]);
    }
}
