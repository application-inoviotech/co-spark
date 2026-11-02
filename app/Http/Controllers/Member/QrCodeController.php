<?php

namespace App\Http\Controllers\Member;

use App\Notifications\QrCodeLink;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** SOW: QR Code — the member can view their QR and have it sent to their phone. */
class QrCodeController extends MemberController
{
    public function send(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user->qrCode?->status === 'active', 422, 'Your QR code is not active yet.');
        abort_unless($user->phone, 422, 'Add a phone number in your profile first.');

        $user->notify(new QrCodeLink);

        // Texts go only to members who opted in (SMS consent); the email always goes
        $byText = $user->sms_opt_in && config('cospark.sms_enabled');

        return response()->json(['message' => $byText
            ? 'QR code link sent to your phone by text and to your email.'
            : 'QR code link sent to your email. Turn on text messages in your profile to also get it by text.']);
    }
}
