<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Lead;
use App\Models\Producer;
use App\Notifications\MemberAlert;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** SOW: Leads Management — quote requests, assignment to producers, follow-ups, notes, status. */
class LeadResource extends Resource
{
    public static string $model = Lead::class;

    public static string $label = 'Leads & quotes';

    public static string $singular = 'Lead';

    public static string $group = 'Insurance';

    public static array $search = ['reference', 'email', 'last_name', 'phone', 'zip'];

    public static bool $editable = true;

    public function query(): Builder
    {
        return Lead::with('producer.user')->latest('id');
    }

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'full_name', 'label' => 'Name'],
            ['key' => 'insurance_type', 'label' => 'Interest', 'type' => 'pill'],
            ['key' => 'source', 'label' => 'Source', 'type' => 'pill'],
            ['key' => 'producer.user.name', 'label' => 'Producer'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'created_at', 'label' => 'Received', 'type' => 'date'],
        ];
    }

    public function filters(): array
    {
        return [
            'status' => ['label' => 'Status', 'options' => self::options(Lead::STATUSES)],
            'assigned' => ['label' => 'Assignment', 'options' => ['no' => 'Unassigned', 'yes' => 'Assigned'],
                'scope' => fn ($q, $v) => $v === 'no' ? $q->whereNull('producer_id') : $q->whereNotNull('producer_id')],
        ];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'producer_id', 'label' => 'Assigned producer', 'type' => 'select', 'options' => $this->producers(), 'half' => true],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::options(Lead::STATUSES), 'half' => true],
            ['name' => 'follow_up_on', 'label' => 'Follow-up date', 'type' => 'date', 'half' => true],
            ['name' => 'producer_notes', 'label' => 'Notes', 'type' => 'textarea'],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'producer_id' => ['nullable', Rule::exists('producers', 'id')->where('status', 'active')],
            'status' => ['required', Rule::in(Lead::STATUSES)],
            'follow_up_on' => ['nullable', 'date'],
            'producer_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function save(Model $lead, array $data): Model
    {
        $newProducer = $data['producer_id'] && (int) $data['producer_id'] !== $lead->producer_id;
        $lead->update($data + ($newProducer ? ['assigned_at' => now()] : []));

        if ($newProducer) {
            $lead->load('producer.user')->producer->user->notify(new MemberAlert('Leads', "New lead assigned · {$lead->full_name}",
                "{$lead->interestLabel()} · {$lead->sourceLabel()}", 'leads', mail: true, portal: 'producer'));
        }

        return $lead;
    }

    public function detail(Model $l): array
    {
        return [['title' => 'Lead', 'rows' => [
            ['Name', $l->full_name], ['Email', $l->email], ['Phone', $l->phone], ['Preferred contact', $l->preferred_contact],
            ['Address', trim("{$l->street}, {$l->city} {$l->state} {$l->zip}", ' ,')], ['Interest', $l->interestLabel()], ['Source', $l->sourceLabel()],
            ['Producer', $l->producer?->user->name], ['Status', $this->format($l->status, 'pill')], ['Follow-up', $l->follow_up_on?->format('M j, Y')],
            ['Notes', $l->producer_notes], ['Customer notes', $l->notes],
        ]], ...$this->answers('Questionnaire', $l->details, ['insured' => 'currently insured', 'name' => 'name']), $this->table('Files', ['File', 'Uploaded', ''], $l->attachments->map(fn ($a) => [$a->original_name, $a->created_at->format('M j, Y'), ['href' => route('admin.files', $a), 'label' => 'Download']]))];
    }

    private function producers(): array
    {
        return ['' => 'Unassigned'] + Producer::with('user')->where('status', 'active')->get()->mapWithKeys(fn ($p) => [$p->id => $p->user->name])->all();
    }
}
