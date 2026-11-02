<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Claim;
use App\Notifications\MemberAlert;
use App\Notifications\StatusUpdate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

/** SOW: claim status, timeline, required actions, outstanding documents and claim messages. */
class ClaimResource extends Resource
{
    public const STATUSES = ['submitted', 'in_review', 'adjuster_assigned', 'awaiting_documents', 'approved', 'denied', 'closed_paid', 'closed'];

    public static string $model = Claim::class;

    public static string $label = 'Claims';

    public static string $singular = 'Claim';

    public static string $group = 'Insurance';

    public static array $search = ['reference', 'policy_number', 'email', 'last_name'];

    public static bool $editable = true;

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Claim'],
            ['key' => 'insurance_type', 'label' => 'Type'],
            ['key' => 'policy_number', 'label' => 'Policy'],
            ['key' => 'email', 'label' => 'Claimant'],
            ['key' => 'incident_date', 'label' => 'Incident', 'type' => 'date'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
        ];
    }

    public function filters(): array
    {
        return ['status' => ['label' => 'Status', 'options' => self::options(self::STATUSES)]];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::options(self::STATUSES), 'half' => true],
            ['name' => 'progress_step', 'label' => 'Timeline step', 'type' => 'select', 'options' => array_map('strval', Claim::STEPS), 'half' => true],
            ['name' => 'required_action', 'label' => 'Required action for the member', 'hint' => 'Leave empty when nothing is needed.'],
            ['name' => 'outstanding_documents', 'label' => 'Outstanding documents', 'type' => 'lines', 'hint' => 'One per line.'],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'status' => ['required', Rule::in(self::STATUSES)],
            'progress_step' => ['required', 'integer', 'between:0,'.(count(Claim::STEPS) - 1)],
            'required_action' => ['nullable', 'string', 'max:255'],
            'outstanding_documents' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(Model $claim, array $data): Model
    {
        $claim->update($data);
        $this->tell($claim, "Claim {$claim->reference} updated", 'Status: '.$this->format($claim->status, 'pill').($claim->required_action ? ". Action needed: {$claim->required_action}" : ''));

        return $claim;
    }

    public function detail(Model $c): array
    {
        return [
            ['title' => 'Claim', 'rows' => [
                ['Claimant', "{$c->first_name} {$c->last_name} · {$c->email} · {$c->phone}"], ['Preferred contact', $c->preferred_contact],
                ['Policy', $c->policy_number], ['Type', "{$c->insurance_type} · {$c->claim_type}"],
                ['Incident', $c->incident_date->format('M j, Y').' '.$c->incident_time.' · '.$c->location], ['Description', $c->description],
                ['Comments', $c->comments], ['Status', $this->format($c->status, 'pill')], ['Timeline', Claim::STEPS[$c->progress_step] ?? '—'],
                ['Required action', $c->required_action], ['Outstanding', implode(', ', $c->outstanding_documents ?? [])],
            ]],
            ...$this->answers('Claim details', $c->details),
            $this->table('Files', ['File', 'Field', 'Uploaded', ''], $c->attachments->map(fn ($a) => [$a->original_name, $a->field, $a->created_at->format('M j, Y'), ['href' => route('admin.files', $a), 'label' => 'Download']])),
            $this->table('Messages', ['From', 'Message', 'Date'], $c->messages->map(fn ($m) => [$m->author_name, $m->body, $m->created_at->format('M j, Y g:i A')])),
        ];
    }

    public function actions(Model $c): array
    {
        return [['key' => 'message', 'label' => 'Message claimant', 'style' => 'copper', 'fields' => [
            ['name' => 'author', 'label' => 'From', 'rules' => ['required', 'string', 'max:80'], 'default' => 'Adjuster · '.auth()->user()?->first_name],
            ['name' => 'body', 'label' => 'Message', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:3000']],
        ]]];
    }

    public function runAction(Model $c, string $action, array $data): string
    {
        $c->messages()->create(['author_name' => $data['author'], 'from_member' => false, 'body' => $data['body']]);
        $c->touch();
        $this->tell($c, "New message on claim {$c->reference}", $data['body']);

        return 'Message sent.';
    }

    /** Members see it in their dashboard; guests who filed without an account get an email. */
    private function tell(Claim $c, string $title, string $text): void
    {
        $c->user
            ? $c->user->notify(new MemberAlert('Claims', $title, $text, 'claims', mail: true))
            : Notification::route('mail', $c->email)->notify(new StatusUpdate($title, $c->first_name, [$text]));
    }
}
