<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\ProducerApplication;
use App\Notifications\StatusUpdate;
use App\Services\ProducerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;

/**
 * SOW: Admin Producer Management — Submitted → Under Review → License Verification →
 * Additional Information Required → Approved / Rejected; all communication by email.
 */
class ProducerApplicationResource extends Resource
{
    public static string $model = ProducerApplication::class;

    public static string $label = 'Applications';

    public static string $singular = 'Producer application';

    public static string $group = 'Producers';

    public static array $search = ['reference', 'email', 'last_name', 'license_number'];

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'first_name', 'label' => 'First name'],
            ['key' => 'last_name', 'label' => 'Last name'],
            ['key' => 'license_status', 'label' => 'License'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'created_at', 'label' => 'Submitted', 'type' => 'date'],
        ];
    }

    public function filters(): array
    {
        return ['status' => ['label' => 'Status', 'options' => self::options(ProducerApplication::STATUSES)]];
    }

    public function detail(Model $a): array
    {
        return [
            ['title' => 'Applicant', 'rows' => [
                ['Name', "{$a->first_name} {$a->last_name}"], ['Email', $a->email], ['Phone', $a->phone],
                ['Address', "{$a->street}, {$a->city} {$a->state} {$a->zip}"], ['Occupation', $a->occupation],
                ['Experience', "{$a->experience} · {$a->years_experience} yrs"], ['Previous company', $a->previous_company],
                ['Lines of business', implode(', ', $a->lines_of_business ?? [])], ['Why COSPARK', $a->reason], ['Comments', $a->comments],
            ]],
            ['title' => 'Licensing & status', 'rows' => [
                ['Licensed status', $a->license_status], ['License', trim("{$a->license_type} · {$a->license_state} · {$a->license_number}", ' ·')],
                ['Issued / expires', trim($a->license_issued_on?->format('M j, Y').' – '.$a->license_expires_on?->format('M j, Y'), ' –')],
                ['Status', $this->format($a->status, 'pill')], ['Internal notes', $a->admin_notes], ['Terms accepted', $a->terms_accepted_at->format('M j, Y g:i A')],
            ]],
            $this->table('Documents', ['File', 'Type', 'Uploaded', ''], $a->attachments->map(fn ($f) => [$f->original_name, $f->field, $f->created_at->format('M j, Y'), ['href' => route('admin.files', $f), 'label' => 'Download']])),
        ];
    }

    public function actions(Model $a): array
    {
        if (in_array($a->status, ['approved', 'rejected'], true)) {
            return [];
        }
        $email = ['name' => 'message', 'label' => 'Email to the applicant', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:2000']];

        return [
            ['key' => 'approve', 'label' => 'Approve & onboard', 'style' => 'copper', 'confirm' => 'Creates the producer account and emails the sign-in details (email and a temporary password).'],
            ['key' => 'status', 'label' => 'Update status', 'style' => 'line', 'fields' => [
                ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::options(['under_review', 'license_verification']), 'rules' => ['required', 'in:under_review,license_verification']],
                ['name' => 'notes', 'label' => 'Internal notes', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:2000'], 'default' => $a->admin_notes],
            ]],
            ['key' => 'request_info', 'label' => 'Request information', 'style' => 'line', 'fields' => [$email]],
            ['key' => 'reject', 'label' => 'Reject', 'style' => 'danger', 'fields' => [$email]],
        ];
    }

    public function runAction(Model $a, string $action, array $data): string
    {
        $email = fn (string $subject, array $lines) => Notification::route('mail', $a->email)->notify(new StatusUpdate("{$subject} — {$a->reference}", $a->first_name, $lines));

        return match ($action) {
            'approve' => tap('Approved — producer account created and login instructions emailed.', fn () => app(ProducerService::class)->approve($a)),
            'status' => tap('Status updated.', function () use ($a, $data, $email) {
                $a->update(['status' => $data['status'], 'admin_notes' => $data['notes'] ?? $a->admin_notes]);
                $email('Your producer application', ['Your application status is now: '.$this->format($data['status'], 'pill').'.']);
            }),
            'request_info' => tap('Request emailed to the applicant.', function () use ($a, $data, $email) {
                $a->update(['status' => 'info_required']);
                $email('Additional information needed', [$data['message'], 'Reply to this email with the requested information or documents.']);
            }),
            'reject' => tap('Application rejected and the applicant emailed.', function () use ($a, $data, $email) {
                $a->update(['status' => 'rejected']);
                $email('Your producer application', [$data['message']]);
            }),
        };
    }
}
