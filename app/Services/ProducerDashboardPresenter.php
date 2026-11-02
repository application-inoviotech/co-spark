<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Commission;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\Producer;
use App\Models\ProducerDocument;
use App\Models\ProducerLicense;
use Illuminate\Support\Str;

/**
 * Builds the producer portal payload consumed by public/assets/js/producer.js
 * (same shape as the prototype's `P` object). Dates are ISO (Y-m-d).
 */
class ProducerDashboardPresenter
{
    public function __construct(private ProducerService $producers) {}

    public function present(Producer $producer): array
    {
        $this->producers->syncBonus($producer);
        $producer->refresh()->load('user.membership', 'licenses', 'application');
        $user = $producer->user;
        $eligibility = $producer->bonusEligibility();
        $application = $producer->application;

        return [
            'status' => $producer->isActive() ? 'active' : 'review',
            'me' => [
                'first' => $user->first_name, 'last' => $user->last_name, 'id' => $producer->producer_number,
                'email' => $user->email, 'phone' => $user->phone, 'biz' => $producer->business_name,
                'addr' => $producer->business_address ?? trim(implode(', ', array_filter([$user->street, $user->city, trim("{$user->state} {$user->zip}")])), ', '),
                'joined' => ($producer->onboarded_on ?? $producer->created_at)->toDateString(), 'npn' => $producer->npn,
            ],
            'lic' => $producer->licenses->sortBy('expires_on')->map(fn (ProducerLicense $l) => [
                'id' => $l->id, 'type' => $l->license_type, 'no' => $l->license_number, 'st' => $l->state,
                'issue' => $l->issued_on?->toDateString(), 'exp' => $l->expires_on->toDateString(), 'status' => $l->statusLabel(),
            ])->values()->all(),
            'docs' => $producer->documents()->oldest()->get()->map(fn (ProducerDocument $d) => [
                'id' => $d->id, 'n' => $d->name, 'date' => $d->uploaded_at?->toDateString(), 's' => ProducerDocument::STATUS_LABELS[$d->status] ?? Str::title($d->status),
                'url' => $d->path ? route('producer.documents.show', $d) : null,
            ])->all(),
            'comm' => $producer->commissions()->orderByDesc('written_on')->get()->map(fn (Commission $c) => [
                'ref' => $c->policy_reference, 'ins' => $c->insured_name, 'line' => $c->line_of_business, 'date' => $c->written_on->toDateString(),
                'prem' => (float) $c->premium, 'base' => (float) $c->commission_base, 'rate' => (float) $c->rate, 'amt' => (float) $c->amount,
                's' => Str::title($c->status), 'paid' => $c->paid_on?->toDateString(),
            ])->all(),
            'leads' => $producer->leads()->latest('assigned_at')->latest()->get()->map(fn (Lead $l) => [
                'id' => $l->reference, 'n' => $l->full_name, 'c' => "{$l->email} · {$l->phone}", 'int' => $l->interestLabel(), 'src' => $l->sourceLabel(),
                'date' => ($l->assigned_at ?? $l->created_at)->toDateString(), 's' => Str::title($l->status), 'note' => $l->producer_notes,
                'follow' => $l->follow_up_on?->toDateString(),
            ])->all(),
            'appts' => $producer->appointments()->orderBy('preferred_date')->get()->map(fn (Appointment $a) => [
                'date' => $a->preferred_date->toDateString(), 'time' => $a->preferred_time, 'type' => $a->type_label, 'who' => $a->name,
                'note' => $a->reason.($a->message ? " — {$a->message}" : ''), 's' => $a->status === 'pending' ? 'Requested' : Str::title($a->status),
            ])->all(),
            'notes' => MemberDashboardPresenter::notifications($user, 'producer'),
            'bonus' => [
                'tokens' => $producer->bonus_tokens,
                'status' => $producer->bonus_status,
                'credited' => $producer->bonus_credited_on?->toDateString(),
                'requested' => $producer->bonus_redemption_requested_at?->toDateString(),
                'start' => $producer->qualification_started_on?->toDateString(),
                'eligibleOn' => $producer->eligibleOn()?->toDateString(),
                'months' => min(Producer::QUALIFICATION_MONTHS, $producer->monthsQualified()),
                'eligibility' => $eligibility,
            ],
            'rate' => (float) $producer->commission_rate,
            'payout' => $producer->payout_label,
            'allowance' => Str::title(str_replace('_', ' ', $producer->auto_allowance_status)),
            'review' => $application ? ['ref' => $application->reference, 'submitted' => $application->created_at->toDateString()] : null,
            'properties' => Partner::active()->where('is_vacation', true)->get(['id', 'name', 'nightly_rate'])->map(fn ($p) => ['id' => $p->id, 'n' => $p->name, 'rate' => (float) ($p->nightly_rate ?? config('membership.vacations.default_nightly_rate'))])->all(),
            'config' => ['discount' => \App\Models\Producer::VACATION_DISCOUNT, 'noticeDays' => \App\Models\VacationRequest::MIN_NOTICE_DAYS, 'states' => config('cospark.us_states')],
        ];
    }
}
