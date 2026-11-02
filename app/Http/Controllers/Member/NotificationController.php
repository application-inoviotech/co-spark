<?php

namespace App\Http\Controllers\Member;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** SOW: Notifications — unread, history, mark as read, by category. */
class NotificationController extends MemberController
{
    public function read(Request $request, string $id): JsonResponse
    {
        $request->user()->notifications()->whereKey($id)->firstOrFail()->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function readAll(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return response()->json(['ok' => true]);
    }
}
