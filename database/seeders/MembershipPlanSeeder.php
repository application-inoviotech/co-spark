<?php

namespace Database\Seeders;

use App\Models\MembershipPlan;
use Illuminate\Database\Seeder;

/** Plans from config/membership.php. Never overwrites admin edits. */
class MembershipPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = config('membership.plans') + [MembershipPlan::SUB_ACCOUNT => config('membership.sub_account_plan')];

        foreach (array_keys($plans) as $order => $key) {
            $plan = $plans[$key];

            MembershipPlan::firstOrCreate(['key' => $key], [
                'name' => $plan['name'],
                'tagline' => $plan['tagline'] ?? null,
                'description' => $plan['description'] ?? null,
                'monthly_fee' => $plan['monthly_fee'],
                'dues_tokens' => $plan['dues_tokens'],
                'insurance_token_percent' => $plan['insurance_token_rate'] * 100,
                'perks' => $plan['perks'],
                'sort_order' => $order,
            ]);
        }
    }
}
