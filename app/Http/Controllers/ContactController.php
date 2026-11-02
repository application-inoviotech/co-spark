<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreContactMessageRequest;
use App\Models\ContactMessage;
use App\Notifications\SubmissionReceived;
use App\Services\SubmissionNotifier;
use Illuminate\Http\JsonResponse;

class ContactController extends Controller
{
    public function __construct(private SubmissionNotifier $notifier) {}

    public function store(StoreContactMessageRequest $request): JsonResponse
    {
        $message = ContactMessage::create($request->validated());

        $this->notifier->notify(
            new SubmissionReceived('We received your message', $message->first_name, $message->reference, [
                "Thanks for contacting COSPARK Insurance Group. We'll reply within one business day.",
            ], sms: false),
            $message->email,
            null,
            'contact message',
            ['From' => "{$message->first_name} {$message->last_name}", 'Email' => $message->email, 'Phone' => $message->phone, 'Message' => $message->message],
        );

        return response()->json(['reference' => $message->reference], 201);
    }
}
