<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/** Sign in for members and producers (SOW: Login → role-based dashboard). */
class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:filter'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = strtolower($credentials['email']);
        $role = $request->input('role') === User::ROLE_PRODUCER ? User::ROLE_PRODUCER : User::ROLE_MEMBER;

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These details don’t match an account.']);
        }

        $user = $request->user();

        if ($user->isAdmin()) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Staff accounts sign in at '.route('admin.login').'.']);
        }

        // The Member / Producer tab must match the account: each signs in through its own portal
        if ($user->role !== $role) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => $user->isProducer()
                ? 'This is a Producer account. Select Producer to sign in.'
                : 'This is a Member account. Select Member to sign in.']);
        }

        if ($user->status === 'suspended') {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'This account is inactive. Please contact COSPARK.']);
        }

        $request->session()->regenerate();

        // Unfinished registration: back to email verification / first payment
        if ($user->needsOnboarding()) {
            return redirect()->route('register');
        }

        return redirect()->to($this->safeNext($request) ?? $user->homeRoute());
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->migrate(true); // keep any admin session in this browser
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }

    /** Only same-site paths are honoured for ?next= to prevent open redirects. */
    private function safeNext(Request $request): ?string
    {
        $next = (string) $request->input('next');
        if ($next === '') {
            return null;
        }

        $host = parse_url($next, PHP_URL_HOST);
        if ($host !== null && $host !== $request->getHost()) {
            return null;
        }

        $path = parse_url($next, PHP_URL_PATH) ?? '';

        return str_starts_with($path, '/') && ! str_starts_with($path, '//') ? $next : null;
    }
}
