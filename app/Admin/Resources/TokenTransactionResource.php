<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\TokenTransaction;
use Illuminate\Database\Eloquent\Builder;

/** SOW: Token & Redemption Management — ledger of DT / IT / VT activity (adjustments are made from a user). */
class TokenTransactionResource extends Resource
{
    public static string $model = TokenTransaction::class;

    public static string $label = 'Token ledger';

    public static string $singular = 'Token transaction';

    public static string $group = 'Billing & tokens';

    public static array $search = ['description', 'reference', 'user.email'];

    public function query(): Builder
    {
        return TokenTransaction::with('user')->latest('id');
    }

    public function columns(): array
    {
        return [
            ['key' => 'created_at', 'label' => 'Date', 'type' => 'date'],
            ['key' => 'user.name', 'label' => 'Member'],
            ['key' => 'token_type', 'label' => 'Token'],
            ['key' => 'kind', 'label' => 'Type', 'type' => 'pill'],
            ['key' => 'amount', 'label' => 'Amount'],
            ['key' => 'description', 'label' => 'Description'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
        ];
    }

    public function filters(): array
    {
        return [
            'token_type' => ['label' => 'Token', 'options' => ['DT' => 'DT', 'IT' => 'IT', 'VT' => 'VT']],
            'kind' => ['label' => 'Type', 'options' => self::options(['earned', 'purchased', 'used', 'transferred_in', 'transferred_out', 'forfeited', 'adjusted'])],
        ];
    }
}
