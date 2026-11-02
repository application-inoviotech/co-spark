<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OneTimeCode;
use App\Models\User;
use App\Services\BenefitsService;
use App\Services\OneTimeCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** SOW: Forgot Password → OTP code → Create New Password (members and producers). */
class PasswordResetController extends Controller
{
    private const SESSION_KEY = 'password_reset_email';

    public function __construct(private OneTimeCodeService $codes) {}

    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /** Always answers the same way so the form can't be used to discover accounts. */
    public function sendCode(Request $request): JsonResponse
    {
        $email = Str::lower($request->validate(['email' => ['required', 'email:filter']])['email']);

        if (User::where('email', $email)->exists()) {
            $this->codes->issue($email, OneTimeCode::RESET_PASSWORD);
        }
        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['sent' => true]);
    }

    public function verifyCode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:filter'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = Str::lower($data['email']);

        if (! $this->codes->verify($email, OneTimeCode::RESET_PASSWORD, $data['code'])) {
            throw ValidationException::withMessages(['code' => 'That code is incorrect or has expired. Request a new one.']);
        }

        $request->session()->put(self::SESSION_KEY, $email);

        return response()->json(['verified' => true]);
    }

    public function update(Request $request, BenefitsService $benefits): JsonResponse
    {
        $email = $request->session()->get(self::SESSION_KEY);
        abort_unless($email, 422, 'Verify the code we emailed you first.');

        $request->validate(['password' => ['required', 'confirmed', Password::min(8)->numbers()]]);

        $user = User::where('email', $email)->firstOrFail();
        $user->forceFill(['password' => $request->input('password'), 'remember_token' => Str::random(60)]);
        // An invited sub-account proves its email by using the emailed code
        $activated = $user->isSubAccount() && $user->status === 'pending';
        if ($activated) {
            $user->forceFill(['status' => 'active', 'email_verified_at' => now()]);
        }
        $user->save();

        // Paid members get their QR with their first dues payment; a sub-account allowed the QR gets it on activation
        if ($activated && $user->hasPermission('QR code') && ! $benefits->qrIneligibility($user)) {
            $benefits->issueQr($user);
        }

        $request->session()->forget(self::SESSION_KEY);

        return response()->json(['updated' => true]);
    }
}
