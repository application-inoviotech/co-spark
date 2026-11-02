<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\MembershipPaymentRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\OneTimeCode;
use App\Models\User;
use App\Services\Billing\AuthorizeNet;
use App\Services\MembershipService;
use App\Services\OneTimeCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Member registration (SOW): create account → verify email by OTP → pay first dues (paid plans).
 * The account is signed in after creation so the wizard can be resumed if interrupted.
 */
class RegisterController extends Controller
{
    public function __construct(
        private MembershipService $memberships,
        private OneTimeCodeService $codes,
        private AuthorizeNet $authorizeNet,
    ) {}

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user && ! $user->needsOnboarding()) {
            return redirect()->to($user->homeRoute());
        }

        $plan = (string) $request->query('plan');

        return view('auth.register', ['wizard' => [
            'plans' => collect(config('membership.plans'))->map(fn ($p) => [
                'n' => $p['name'],
                'p' => (float) $p['monthly_fee'],
                'dt' => $p['dues_tokens'],
                'perks' => $p['perks'],
            ]),
            'freq' => config('membership.frequencies'),
            'freqs' => ['associate' => array_keys(config('membership.frequencies')), 'premium' => array_keys(config('membership.frequencies'))],
            'acceptJs' => $this->authorizeNet->clientConfig(),
            'states' => config('cospark.us_states'),
            'state' => $user ? $this->resumeState($user) : [
                'plan' => array_key_exists($plan, config('membership.plans')) ? $plan : 'premium',
                'email' => (string) $request->query('email', ''),
            ],
        ]]);
    }

    public function store(RegisterRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first'],
                'last_name' => $data['last'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'street' => $data['street'],
                'street2' => $data['street2'] ?? null,
                'city' => $data['city'],
                'state' => $data['stt'],
                'zip' => $data['zip'],
                'role' => User::ROLE_MEMBER,
                'status' => 'pending',
                'sms_opt_in' => (bool) ($data['sms'] ?? false),
                'sms_opt_in_at' => ($data['sms'] ?? false) ? now() : null,
                'password' => $data['pw'],
            ]);
            $this->memberships->start($user, $data['plan']);

            return $user;
        });

        // login() migrates the session id; the CSRF token is kept so the open wizard can continue
        Auth::login($user);

        $this->codes->issue($user->email, OneTimeCode::VERIFY_EMAIL);

        return response()->json(['next' => 'verify'], 201);
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate(['code' => ['required', 'digits:6']]);
        $user = $request->user();

        if (! $user->email_verified_at) {
            if (! $this->codes->verify($user->email, OneTimeCode::VERIFY_EMAIL, $request->input('code'))) {
                throw ValidationException::withMessages(['code' => 'That code is incorrect or has expired. Request a new one.']);
            }
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        $membership = $user->membership;
        $this->memberships->activateIfFree($membership);

        return response()->json(['next' => $membership->isPaid() ? 'payment' : 'done']);
    }

    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_if($user->email_verified_at !== null, 422, 'Your email is already verified.');

        $this->codes->issue($user->email, OneTimeCode::VERIFY_EMAIL);

        return response()->json(['sent' => true]);
    }

    public function payment(MembershipPaymentRequest $request): JsonResponse
    {
        $user = $request->user();
        $membership = $user->membership;

        abort_unless($user->email_verified_at, 422, 'Verify your email before paying.');
        abort_unless($membership && $membership->isPaid() && $membership->status === 'pending', 422, 'This membership does not need a payment.');

        $method = $this->authorizeNet->savePaymentMethod($user, $request->input('payment_descriptor'), $request->input('payment_method'), makeDefault: true);
        $payment = $this->memberships->payDues($membership, $request->input('freq'), $method);

        if (! $payment->succeeded()) {
            throw ValidationException::withMessages(['payment_method' => $payment->failure_reason.' Try another card.']);
        }

        // Recurring billing unticked: the paid period runs out and is not renewed
        if (! $request->boolean('auto_renew', true)) {
            $membership->update(['auto_renew' => false]);
        }

        return response()->json([
            'reference' => $payment->reference,
            'dues_tokens' => $membership->refresh()->periodDuesTokens(),
        ], 201);
    }

    /** Where an interrupted registration continues. */
    private function resumeState(User $user): array
    {
        $membership = $user->membership;

        return [
            'plan' => $membership->plan,
            'freq' => $membership->billing_frequency,
            'first' => $user->first_name,
            'email' => $user->email,
            'sms' => $user->sms_opt_in,
            'locked' => true,
            'step' => $user->email_verified_at ? 'payment' : 'verify',
        ];
    }
}
