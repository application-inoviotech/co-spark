<?php

namespace App\Http\Controllers\Producer;

use App\Models\Lead;
use App\Models\Partner;
use App\Models\Producer;
use App\Models\ProducerDocument;
use App\Models\VacationRequest;
use App\Notifications\MemberAlert;
use App\Notifications\NewSubmission;
use App\Services\ProducerDashboardPresenter;
use App\Services\ProducerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Producer portal data and actions (SOW: Producer Dashboard). */
class PortalController extends ProducerController
{
    public function data(Request $request, ProducerDashboardPresenter $presenter): JsonResponse
    {
        return response()->json($presenter->present($this->producer($request)));
    }

    /** SOW: Producer Leads — track follow-up status, date and notes on assigned leads. */
    public function updateLead(Request $request, Lead $lead): JsonResponse
    {
        $producer = $this->activeProducer($request);
        abort_unless($lead->producer_id === $producer->id, 404);

        $data = $request->validate([
            'status' => ['required', Rule::in(Lead::STATUSES)],
            'follow_up_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $lead->update(['status' => $data['status'], 'follow_up_on' => $data['follow_up_on'] ?? null, 'producer_notes' => $data['notes'] ?? null]);

        return response()->json(['ok' => true]);
    }

    /** Add a license or submit a renewal; it stays "Pending verification" until COSPARK verifies it. */
    public function storeLicense(Request $request): JsonResponse
    {
        $producer = $this->producer($request);
        $data = $request->validate([
            'license_id' => ['nullable', 'integer'],
            'license_type' => ['required', Rule::in(['Property & Casualty', 'Life & Health'])],
            'state' => ['required', Rule::in(config('cospark.us_states'))],
            'license_number' => ['required', 'string', 'max:60'],
            'expires_on' => ['required', 'date', 'after:today'],
            'document' => ['nullable', 'file', 'max:'.config('cospark.upload_max_kb'), 'mimes:'.config('cospark.upload_mimes')],
        ], ['expires_on.after' => 'Enter an expiration date in the future.']);

        $license = ! empty($data['license_id']) ? $producer->licenses()->findOrFail($data['license_id']) : $producer->licenses()->make();
        $license->fill([
            'license_type' => $data['license_type'], 'state' => $data['state'], 'license_number' => $data['license_number'],
            'expires_on' => $data['expires_on'], 'issued_on' => $license->issued_on ?? today(), 'verified_at' => null,
        ]);
        if ($request->hasFile('document')) {
            $license->document_path = $request->file('document')->store("producers/{$producer->id}/licenses");
        }
        $license->save();

        $this->notifyOffice('producer license for verification', $producer->producer_number, ['Producer' => $producer->user->name, 'License' => "{$license->license_type} · {$license->state} · {$license->license_number}"]);

        return response()->json(['ok' => true], 201);
    }

    /** Upload a requested document (document_id) or an additional one. */
    public function uploadDocument(Request $request): JsonResponse
    {
        $producer = $this->producer($request);
        $data = $request->validate([
            'document_id' => ['nullable', 'integer'],
            'file' => ['required', 'file', 'max:'.config('cospark.upload_max_kb'), 'mimes:'.config('cospark.upload_mimes')],
        ]);
        $file = $request->file('file');

        $document = ! empty($data['document_id'])
            ? $producer->documents()->findOrFail($data['document_id'])
            : $producer->documents()->make(['name' => pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)]);

        $document->fill(['path' => $file->store("producers/{$producer->id}/documents"), 'status' => 'pending_review', 'uploaded_at' => now()])->save();

        $this->notifyOffice('producer document for review', $producer->producer_number, ['Producer' => $producer->user->name, 'Document' => $document->name]);

        return response()->json(['ok' => true], 201);
    }

    public function showDocument(Request $request, ProducerDocument $document): StreamedResponse
    {
        abort_unless($document->producer_id === $this->producer($request)->id && $document->path, 404);

        return Storage::download($document->path, Str::slug($document->name).'.'.pathinfo($document->path, PATHINFO_EXTENSION));
    }

    /** Contact details the producer may change; legal name and IDs are changed by the office. */
    public function updateProfile(Request $request): JsonResponse
    {
        $producer = $this->producer($request);
        $user = $producer->user;
        $data = $request->validate([
            'phone' => ['required', 'string', 'max:40', new \App\Rules\PhoneNumber],
            'business_name' => ['nullable', 'string', 'max:160'],
            'business_address' => ['nullable', 'string', 'max:255'],
        ]);

        // The sign-in email is not editable by the producer; any "email" sent is ignored
        DB::transaction(function () use ($user, $producer, $data) {
            $user->update(['phone' => $data['phone']]);
            $producer->update(['business_name' => $data['business_name'] ?? null, 'business_address' => $data['business_address'] ?? null]);
        });

        return response()->json(['ok' => true]);
    }

    /** SOW: 10% producer vacation discount for the producer, family and friends. */
    public function requestVacation(Request $request): JsonResponse
    {
        $producer = $this->activeProducer($request);
        $request->validate(['check_in' => ['required', 'date']]);
        $earliest = today()->addDays(VacationRequest::MIN_NOTICE_DAYS);
        $data = $request->validate([
            'property_id' => ['required', 'integer'],
            'check_in' => ['required', 'date', 'after_or_equal:'.$earliest->toDateString()],
            'check_out' => ['required', 'date', 'after:check_in', 'before:'.Carbon::parse($request->input('check_in'))->addDays(31)->toDateString()],
            'traveller' => ['required', Rule::in(['Me', 'Family', 'Friends'])],
        ], [
            'check_in.after_or_equal' => 'Choose a date at least 90 days from today ('.$earliest->format('M j, Y').').',
            'check_out.after' => 'Check-out must be after check-in.',
        ]);

        $property = Partner::active()->where('is_vacation', true)->findOrFail($data['property_id']);
        $nights = Carbon::parse($data['check_in'])->diffInDays($data['check_out']);
        $rate = (float) ($property->nightly_rate ?? config('membership.vacations.default_nightly_rate'));
        $discount = Producer::VACATION_DISCOUNT;

        $vacation = $producer->user->vacationRequests()->create([
            'partner_id' => $property->id, 'check_in' => $data['check_in'], 'check_out' => $data['check_out'], 'guests' => 1,
            'traveller' => $data['traveller'], 'discount_rate' => $discount, 'notes' => "Producer discount · {$producer->producer_number}",
            'payment_amount' => round($nights * $rate * (1 - $discount), 2),
        ]);

        $producer->user->notify(new MemberAlert('Vacations', 'Discounted stay requested', "{$property->name} · 10% producer discount", 'vacations', mail: true, portal: 'producer'));

        return response()->json(['reference' => $vacation->reference], 201);
    }

    public function redeemBonus(Request $request, ProducerService $producers): JsonResponse
    {
        $producers->requestRedemption($this->activeProducer($request));

        return response()->json(['ok' => true]);
    }

    /** Commission statement as CSV. */
    public function exportCommissions(Request $request): StreamedResponse
    {
        $producer = $this->activeProducer($request);

        return response()->streamDownload(function () use ($producer) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Policy', 'Insured', 'Line', 'Written', 'Premium', 'Commission base', 'Rate', 'Amount', 'Status', 'Paid on']);
            foreach ($producer->commissions()->orderByDesc('written_on')->cursor() as $c) {
                fputcsv($out, \App\Support\CsvRow::safe([$c->policy_reference, $c->insured_name, $c->line_of_business, $c->written_on->toDateString(), $c->premium, $c->commission_base, ($c->rate * 100).'%', $c->amount, $c->status, $c->paid_on?->toDateString()]));
            }
            fclose($out);
        }, "commission-statement-{$producer->producer_number}.csv", ['Content-Type' => 'text/csv']);
    }

    private function notifyOffice(string $kind, string $reference, array $summary): void
    {
        Notification::route('mail', config('cospark.admin_notify_email'))->notify(new NewSubmission($kind, $reference, $summary));
    }
}
