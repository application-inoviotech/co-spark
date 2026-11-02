<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Inertia\Response;

/** Staff sign-in on the separate `admin` guard, independent of any member/producer session. */
class AuthController extends Controller
{
    public function create(): Response
    {
        return inertia('Auth/Login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email:filter'],
            'password' => ['required', 'string'],
        ]);
        $credentials['email'] = strtolower($credentials['email']);

        $guard = Auth::guard('admin');
        $valid = fn ($user) => $user->isAdmin() && $user->status === 'active';

        if (! $guard->attemptWhen($credentials, $valid, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'These details don’t match an admin account.']);
        }

        $request->session()->migrate(true);

        return redirect()->intended(route('admin.dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();
        $request->session()->migrate(true); // a member/producer login in this browser stays signed in
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
