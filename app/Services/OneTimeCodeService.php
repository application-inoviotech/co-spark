<?php

namespace App\Services;

use App\Models\OneTimeCode;
use App\Notifications\OneTimeCodeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

/** Six-digit email codes for account verification and password reset (SOW: OTP). */
class OneTimeCodeService
{
    public function issue(string $email, string $purpose): void
    {
        $config = config('membership.otp');
        // Testing: a fixed code (e.g. 123456) when OTP_FIXED_CODE is set in .env; leave it unset in production.
        $code = $config['fixed_code']
            ?: str_pad((string) random_int(0, 10 ** $config['length'] - 1), $config['length'], '0', STR_PAD_LEFT);

        // Only the newest code for an email + purpose is valid
        OneTimeCode::where(compact('email', 'purpose'))->whereNull('consumed_at')->update(['consumed_at' => now()]);

        OneTimeCode::create([
            'email' => $email,
            'purpose' => $purpose,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes($config['ttl_minutes']),
        ]);

        Notification::route('mail', $email)->notify(new OneTimeCodeNotification($code, $purpose, $config['ttl_minutes']));
    }

    /** Checks and consumes the code. Too many wrong attempts invalidate it. */
    public function verify(string $email, string $purpose, string $code): bool
    {
        $otp = OneTimeCode::where(compact('email', 'purpose'))
            ->whereNull('consumed_at')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        if (! $otp || $otp->attempts >= config('membership.otp.max_attempts')) {
            return false;
        }

        if (! Hash::check($code, $otp->code_hash)) {
            $otp->increment('attempts');

            return false;
        }

        $otp->update(['consumed_at' => now()]);

        return true;
    }
}
