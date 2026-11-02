<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Commission;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** SOW: commission history for producer reporting and administrative review. */
class CommissionResource extends Resource
{
    public static string $model = Commission::class;

    public static string $label = 'Commissions';

    public static string $singular = 'Commission';

    public static string $group = 'Producers';

    public static array $search = ['policy_reference', 'insured_name', 'producer.producer_number'];

    public function query(): Builder
    {
        return Commission::with('producer.user')->latest('written_on');
    }

    public function title(Model $c): string
    {
        return "{$c->policy_reference} · {$c->insured_name}";
    }

    public function columns(): array
    {
        return [
            ['key' => 'policy_reference', 'label' => 'Policy'],
            ['key' => 'producer.user.name', 'label' => 'Producer'],
            ['key' => 'insured_name', 'label' => 'Insured'],
            ['key' => 'written_on', 'label' => 'Written', 'type' => 'date'],
            ['key' => 'premium', 'label' => 'Premium', 'type' => 'money'],
            ['key' => 'amount', 'label' => 'Commission', 'type' => 'money'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
        ];
    }

    public function filters(): array
    {
        return ['status' => ['label' => 'Status', 'options' => self::options(['pending', 'paid'])]];
    }

    public function actions(Model $c): array
    {
        return $c->status === 'pending' ? [['key' => 'paid', 'label' => 'Mark paid', 'style' => 'copper', 'fields' => [['name' => 'paid_on', 'label' => 'Paid on', 'type' => 'date', 'rules' => ['required', 'date'], 'default' => today()->toDateString()]]]] : [];
    }

    public function runAction(Model $c, string $action, array $data): string
    {
        $c->update(['status' => 'paid', 'paid_on' => $data['paid_on']]);

        return 'Commission marked as paid.';
    }
}
