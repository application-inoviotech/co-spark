<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/** SOW: Profile & Account Settings — contact details, profile image, password. */
class ProfileController extends MemberController
{
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'first' => ['required', 'string', 'max:80'],
            'last' => ['required', 'string', 'max:80'],
            'phone' => ['nullable', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'street' => ['nullable', 'string', 'max:255'],
            'street2' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:120'],
            'st' => ['nullable', Rule::in(config('cospark.us_states'))],
            'zip' => ['nullable', 'regex:/^\d{5}$/'],
            'sms' => ['boolean'],
        ], ['st.in' => 'Use a two-letter state code, e.g. MI.', 'zip.regex' => 'Enter a 5-digit ZIP code.']);

        // The sign-in email is not editable by the member; any "email" sent is ignored
        $sms = (bool) ($data['sms'] ?? false);
        $user->update([
            'first_name' => $data['first'], 'last_name' => $data['last'],
            'phone' => $data['phone'] ?? null, 'street' => $data['street'] ?? null, 'street2' => $data['street2'] ?? null,
            'city' => $data['city'] ?? null, 'state' => $data['st'] ?? null, 'zip' => $data['zip'] ?? null,
            'sms_opt_in' => $sms, 'sms_opt_in_at' => $sms ? ($user->sms_opt_in_at ?? now()) : null,
        ]);

        return response()->json(['ok' => true]);
    }

    public function avatar(Request $request): JsonResponse
    {
        $request->validate(['avatar' => ['required', 'image', 'max:4096']]);
        $user = $request->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }
        $user->update(['avatar_path' => $request->file('avatar')->store('avatars', 'public')]);

        return response()->json(['url' => Storage::disk('public')->url($user->avatar_path)]);
    }

    public function password(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::min(8)->numbers()],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => 'Your current password is incorrect.']);
        }

        $request->user()->update(['password' => $data['password']]);

        return response()->json(['ok' => true]);
    }
}
