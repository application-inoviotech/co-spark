<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreQuickQuoteRequest;
use App\Http\Requests\StoreQuoteRequest;
use App\Models\Lead;
use App\Models\QuoteDraft;
use App\Notifications\SubmissionReceived;
use App\Services\SubmissionNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/** Get a Quote: the dynamic quote engine and the home page mini quote. Both create a lead. */
class QuoteController extends Controller
{
    public function __construct(private SubmissionNotifier $notifier) {}

    public function create(Request $request): View
    {
        // ?resume=CODE continues a saved quote (SOW: Retrieve a Saved Quote)
        $draft = $request->filled('resume') ? QuoteDraft::findValid($request->query('resume')) : null;

        return view('pages.quote', [
            'quoteTypes' => config('cospark.quote_types'),
            'draft' => $draft ? ['state' => $draft->state, 'step' => $draft->step] : null,
        ]);
    }

    public function store(StoreQuoteRequest $request): JsonResponse
    {
        $data = $request->validated();

        $lead = DB::transaction(function () use ($request, $data) {
            $lead = Lead::create([
                'source' => 'quote',
                'insurance_type' => $request->typeKey(),
                'zip' => $data['zip'],
                'first_name' => $data['first'],
                'last_name' => $data['last'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'preferred_contact' => $data['pref'],
                'street' => $data['street'],
                'city' => $data['city'],
                'state' => $data['st'],
                'notes' => $data['notes'] ?? null,
                'details' => $request->details(),
                'user_id' => $request->user()?->id,
            ]);
            $lead->storeAttachments($request->uploadedFiles(), 'leads');

            return $lead;
        });

        $this->notifyLead($lead, $data['type']);

        return response()->json(['reference' => $lead->reference], 201);
    }

    public function quick(StoreQuickQuoteRequest $request): JsonResponse
    {
        $data = $request->validated();

        $lead = Lead::create([
            'source' => 'home',
            'insurance_type' => strtolower(str_replace(' ', '-', $data['cov'])),
            'zip' => $data['zip'],
            'first_name' => $data['first'],
            'last_name' => $data['last'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'street' => $data['street'],
            'city' => $data['city'],
            'state' => $data['st'],
            'details' => ['coverage' => $data['cov']],
            'user_id' => $request->user()?->id,
        ]);

        $this->notifyLead($lead, $data['cov']);

        return response()->json(['reference' => $lead->reference], 201);
    }

    private function notifyLead(Lead $lead, string $typeLabel): void
    {
        $this->notifier->notify(
            new SubmissionReceived('Quote request received', $lead->first_name, $lead->reference, [
                "Thanks for requesting a {$typeLabel} quote. An agent will review your details and compare carriers.",
                'We will contact you within one business day.',
            ]),
            $lead->email,
            $lead->phone,
            'quote request',
            ['Type' => $typeLabel, 'Name' => $lead->full_name, 'Email' => $lead->email, 'Phone' => $lead->phone, 'ZIP' => $lead->zip],
        );
    }
}
