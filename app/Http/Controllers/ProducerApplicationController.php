<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProducerApplicationRequest;
use App\Models\ProducerApplication;
use App\Notifications\SubmissionReceived;
use App\Services\SubmissionNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

/** Become a Producer → Apply Now (SOW: application stored as Submitted / Under Review). */
class ProducerApplicationController extends Controller
{
    public function __construct(private SubmissionNotifier $notifier) {}

    public function store(StoreProducerApplicationRequest $request): JsonResponse
    {
        $data = $request->validated();

        $application = DB::transaction(function () use ($request, $data) {
            $application = ProducerApplication::create([
                'first_name' => $data['first'],
                'last_name' => $data['last'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'street' => $data['street'],
                'city' => $data['city'],
                'state' => $data['st'],
                'zip' => $data['zip'],
                'occupation' => $data['occ'],
                'experience' => $data['exp'],
                'years_experience' => $data['years'],
                'previous_company' => $data['prev'] ?? null,
                'lines_of_business' => $data['lines'] ?? [],
                'license_status' => $data['lic'],
                'license_number' => $data['licno'] ?? null,
                'license_type' => $data['lictype'] ?? null,
                'license_state' => $data['licst'] ?? null,
                'license_issued_on' => $data['issue'] ?? null,
                'license_expires_on' => $data['exp_d'] ?? null,
                'reason' => $data['why'],
                'comments' => $data['comments'] ?? null,
                'status' => 'submitted',
                'terms_accepted_at' => now(),
            ]);
            $application->storeAttachments($request->uploadedFiles(), 'producer-applications');

            return $application;
        });

        $this->notifier->notify(
            new SubmissionReceived('Producer application received', $application->first_name, $application->reference, [
                'Thank you for applying to become a COSPARK producer. Your application is now Submitted / Under Review.',
                'Our team reviews applications within 5 business days and will contact you by email about next steps.',
            ]),
            $application->email,
            $application->phone,
            'producer application',
            ['Applicant' => "{$application->first_name} {$application->last_name}", 'Email' => $application->email, 'License' => trim("{$application->license_status} {$application->license_state}")],
        );

        return response()->json(['reference' => $application->reference], 201);
    }
}
