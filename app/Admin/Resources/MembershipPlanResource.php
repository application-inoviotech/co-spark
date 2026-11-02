<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\MembershipPlan;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Plans, fees and token rules (applied over config/membership.php). */
class MembershipPlanResource extends Resource
{
    public static string $model = MembershipPlan::class;

    public static string $label = 'Membership plans';

    public static string $singular = 'Membership plan';

    public static string $group = 'Website content';

    public static bool $editable = true;

    public function query(): Builder
    {
        return MembershipPlan::orderBy('sort_order');
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Plan'], ['key' => 'monthly_fee', 'label' => 'Monthly fee', 'type' => 'money'],
            ['key' => 'dues_tokens', 'label' => 'Dues Tokens / month'], ['key' => 'insurance_token_percent', 'label' => 'Insurance Tokens (% of premium)'],
        ];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'name', 'label' => 'Name', 'half' => true],
            ['name' => 'tagline', 'label' => 'Tagline', 'half' => true],
            ['name' => 'description', 'label' => 'Description', 'type' => 'textarea'],
            ['name' => 'monthly_fee', 'label' => 'Monthly fee ($)', 'type' => 'number', 'half' => true, 'hint' => 'Charged from the next payment: new sign-ups, plan changes and each member\'s next renewal.'],
            ['name' => 'dues_tokens', 'label' => 'Dues Tokens per paid month', 'type' => 'number', 'half' => true],
            ['name' => 'insurance_token_percent', 'label' => 'Insurance Tokens (% of eligible annual premium)', 'type' => 'number', 'half' => true],
            ['name' => 'perks', 'label' => 'Dashboard perks', 'type' => 'lines'],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'name' => ['required', 'string', 'max:40'],
            'tagline' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:255'],
            'monthly_fee' => $model->key === 'free' ? ['required', 'numeric', 'in:0'] : ['required', 'numeric', 'min:0.5', 'max:10000'],
            'dues_tokens' => ['required', 'integer', 'min:0', 'max:1000'],
            'insurance_token_percent' => ['required', 'numeric', 'min:0', 'max:100'],
            'perks' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
