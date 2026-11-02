<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Admin-editable membership plan. Saved plans override config('membership.plans') and
 * config('membership.sub_account_*') at boot, so the rest of the app keeps reading plans from config.
 */
class MembershipPlan extends Model
{
    public const SUB_ACCOUNT = 'sub_account';

    protected $fillable = [
        'key', 'name', 'tagline', 'description', 'monthly_fee', 'dues_tokens', 'insurance_token_percent', 'perks',
        'sort_order',
    ];

    protected $casts = ['monthly_fee' => 'float', 'dues_tokens' => 'integer', 'insurance_token_percent' => 'float', 'perks' => 'array'];

    public static function applyToConfig(): void
    {
        $plans = Cache::rememberForever('membership.plans', fn () => Schema::hasTable('membership_plans') ? self::orderBy('sort_order')->get()->toArray() : []);
        foreach ($plans as $plan) {
            $values = [
                'name' => $plan['name'],
                'tagline' => $plan['tagline'],
                'description' => $plan['description'],
                'monthly_fee' => $plan['monthly_fee'],
                'dues_tokens' => $plan['dues_tokens'],
                'insurance_token_rate' => $plan['insurance_token_percent'] / 100,
                'perks' => $plan['perks'] ?? [],
            ];
            if ($plan['key'] === self::SUB_ACCOUNT) {
                config(['membership.sub_account_plan' => $values, 'membership.sub_account_fee' => $plan['monthly_fee']]);
            } else {
                config(["membership.plans.{$plan['key']}" => $values]);
            }
        }
    }

    /** "$25" or "$8.88" */
    public static function money(float $amount): string
    {
        return '$'.(floor($amount) == $amount ? number_format($amount) : number_format($amount, 2));
    }

    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('membership.plans');
            self::applyToConfig(); // the rest of this request (and long-running workers) see the new values too
        });
    }
}
