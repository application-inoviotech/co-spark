<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\VacationRequest;
use App\Services\BenefitsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** SOW: Vacation Management — review requests, 90-day notice, token usage, payments, statuses. */
class VacationRequestResource extends Resource
{
    public static string $model = VacationRequest::class;

    public static string $label = 'Vacation requests';

    public static string $singular = 'Vacation request';

    public static string $group = 'Benefits';

    public static array $search = ['reference', 'user.email', 'user.last_name'];

    public function query(): Builder
    {
        return VacationRequest::with('user', 'partner')->latest('id');
    }

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Request'],
            ['key' => 'user.name', 'label' => 'Member'],
            ['key' => 'partner.name', 'label' => 'Property'],
            ['key' => 'check_in', 'label' => 'Check-in', 'type' => 'date'],
            ['key' => 'tokens_applied', 'label' => 'DT'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
        ];
    }

    public function filters(): array
    {
        return ['status' => ['label' => 'Status', 'options' => self::options(['pending', 'confirmed', 'declined', 'cancelled', 'completed'])]];
    }

    public function detail(Model $v): array
    {
        return [['title' => 'Request', 'rows' => [
            ['Member', $v->user->name.' · '.$v->user->email.' · '.$v->user->member_number], ['Property', "{$v->partner->name} · {$v->partner->location}"],
            ['Dates', $v->check_in->format('M j, Y').' – '.$v->check_out->format('M j, Y')." ({$v->nights()} nights)"], ['Guests', $v->guests],
            ['Producer discount', $v->discount_rate > 0 ? ($v->discount_rate * 100).'% · '.$v->traveller : null],
            ['Tokens applied', "{$v->tokens_applied} DT"], ['Balance to pay', '$'.number_format($v->payment_amount, 2).' · '.$this->format($v->payment_status, 'pill')],
            ['Status', $this->format($v->status, 'pill')], ['Member notes', $v->notes], ['Instructions', $v->instructions], ['Requested', $v->created_at->format('M j, Y')],
        ]]];
    }

    public function actions(Model $v): array
    {
        $note = ['name' => 'note', 'label' => 'Message to the member', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']];

        return array_values(array_filter([
            $v->status === 'pending' ? ['key' => 'confirm', 'label' => 'Confirm stay', 'style' => 'copper', 'fields' => [['name' => 'instructions', 'label' => 'Check-in instructions', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:1000']]]] : null,
            $v->status === 'pending' ? ['key' => 'declined', 'label' => 'Decline', 'style' => 'danger', 'fields' => [$note]] : null,
            $v->status === 'confirmed' ? ['key' => 'completed', 'label' => 'Mark completed', 'style' => 'line'] : null,
            $v->status === 'confirmed' && $v->payment_status === 'pending' ? ['key' => 'paid', 'label' => 'Balance received', 'style' => 'line'] : null,
            in_array($v->status, ['pending', 'confirmed'], true) ? ['key' => 'cancelled', 'label' => 'Cancel', 'style' => 'danger', 'confirm' => 'Deducted tokens are returned to the member.', 'fields' => [$note]] : null,
        ]));
    }

    public function runAction(Model $v, string $action, array $data): string
    {
        $benefits = app(BenefitsService::class);

        return match ($action) {
            'confirm' => tap('Stay confirmed and tokens deducted.', fn () => $benefits->confirmVacation($v, $data['instructions'] ?? null)),
            'paid' => tap('Payment recorded.', fn () => $v->update(['payment_status' => 'paid'])),
            default => tap('Request updated.', fn () => $benefits->setVacationStatus($v, $action, $data['note'] ?? null)),
        };
    }
}
