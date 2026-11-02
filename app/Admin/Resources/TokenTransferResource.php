<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\TokenTransfer;
use App\Services\BenefitsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** SOW: Token Transfer — reviewed by COSPARK before completion. */
class TokenTransferResource extends Resource
{
    public static string $model = TokenTransfer::class;

    public static string $label = 'Token transfers';

    public static string $singular = 'Token transfer';

    public static string $group = 'Billing & tokens';

    public static array $search = ['reference', 'sender.email', 'recipient.email'];

    public function query(): Builder
    {
        return TokenTransfer::with('sender', 'recipient')->latest('id');
    }

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'sender.name', 'label' => 'From'],
            ['key' => 'recipient.name', 'label' => 'To'],
            ['key' => 'quantity', 'label' => 'Qty'],
            ['key' => 'token_type', 'label' => 'Token'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'created_at', 'label' => 'Requested', 'type' => 'date'],
        ];
    }

    public function filters(): array
    {
        return ['status' => ['label' => 'Status', 'options' => self::options(['pending', 'completed', 'rejected'])]];
    }

    public function detail(Model $t): array
    {
        return [['title' => 'Transfer', 'rows' => [
            ['From', $t->sender->name.' · '.$t->sender->email], ['To', $t->recipient->name.' · '.$t->recipient->email],
            ['Tokens', "{$t->quantity} {$t->token_type}"], ['Note', $t->note], ['Status', $this->format($t->status, 'pill')],
            ['Hold-harmless accepted', $t->indemnity_accepted_at->format('M j, Y g:i A')], ['Requested', $t->created_at->format('M j, Y')],
        ]]];
    }

    public function actions(Model $t): array
    {
        return $t->status !== 'pending' ? [] : [
            ['key' => 'approve', 'label' => 'Approve transfer', 'style' => 'copper'],
            ['key' => 'reject', 'label' => 'Reject', 'style' => 'danger', 'fields' => [['name' => 'reason', 'label' => 'Reason sent to the member', 'type' => 'textarea', 'rules' => ['nullable', 'string', 'max:500']]]],
        ];
    }

    public function runAction(Model $t, string $action, array $data): string
    {
        $benefits = app(BenefitsService::class);

        return match ($action) {
            'approve' => tap('Transfer completed.', fn () => $benefits->approveTransfer($t)),
            'reject' => tap('Transfer rejected; tokens returned.', fn () => $benefits->rejectTransfer($t, $data['reason'] ?? null)),
        };
    }
}
