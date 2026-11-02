<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Notifications\SubmissionReceived;
use App\Services\SubmissionNotifier;
use Illuminate\Http\JsonResponse;

class AppointmentController extends Controller
{
    public function __construct(private SubmissionNotifier $notifier) {}

    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        $appointment = Appointment::create([
            'type' => $data['type'],
            'preferred_date' => $data['a_date'],
            'preferred_time' => $data['a_time'],
            'name' => $data['a_name'],
            'email' => $data['a_email'],
            'phone' => $data['a_phone'],
            'reason' => $data['a_reason'] ?? null,
            'message' => $data['a_msg'] ?? null,
            'user_id' => $request->user()?->id,
        ]);

        $when = $appointment->preferred_date->format('l, F j').' at '.$appointment->preferred_time;

        $this->notifier->notify(
            new SubmissionReceived('Appointment requested', $appointment->name, $appointment->reference, [
                "{$appointment->type_label} on {$when}. We'll confirm the exact time shortly.",
            ]),
            $appointment->email,
            $appointment->phone,
            'appointment request',
            ['Type' => $appointment->type_label, 'When' => $when, 'Name' => $appointment->name, 'Phone' => $appointment->phone, 'Reason' => $appointment->reason],
        );

        return response()->json(['reference' => $appointment->reference], 201);
    }
}
