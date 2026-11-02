<?php

namespace App\Support;

use App\Models\MembershipPlan;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Website copy. Each page is defined in resources/content/{slug}.php as
 * ['name' => …, 'fields' => [key => [label, type, default, hint?]]]; admin edits saved on the
 * Page row override the defaults. Types: text, textarea (line breaks kept), lines (one item per
 * line, cells separated by " | ").
 */
class Content
{
    /** @var array<string, array> */
    private array $values = [];

    private ?array $placeholders = null;

    /** @return string[] */
    public static function slugs(): array
    {
        return array_map(fn ($file) => basename($file, '.php'), glob(resource_path('content/*.php')));
    }

    public static function definition(string $slug): array
    {
        return require resource_path("content/{$slug}.php");
    }

    /** @return array<string, mixed> key => default value */
    public static function defaults(string $slug): array
    {
        return array_map(fn ($field) => $field[2], self::definition($slug)['fields']);
    }

    /** "page.key" → stored value (or the default), with plan placeholders filled in. */
    public function get(string $key): mixed
    {
        [$slug, $field] = explode('.', $key, 2);
        $value = $this->page($slug)[$field] ?? null;

        return is_array($value) ? array_map(fn ($v) => strtr($v, $this->placeholders()), $value) : strtr((string) $value, $this->placeholders());
    }

    /**
     * Live plan values usable in any copy, so prices and token rules edited under Membership
     * plans show everywhere: {premium.fee} "$25", {premium.dt} "12", {premium.it} "7%", {premium.pct} "7".
     *
     * @return array<string, string>
     */
    public function placeholders(): array
    {
        return $this->placeholders ??= collect(config('membership.plans'))
            ->put(MembershipPlan::SUB_ACCOUNT, config('membership.sub_account_plan'))
            ->flatMap(fn ($plan, $key) => [
                "{{$key}.fee}" => MembershipPlan::money($plan['monthly_fee']),
                "{{$key}.dt}" => (string) $plan['dues_tokens'],
                "{{$key}.it}" => round($plan['insurance_token_rate'] * 100, 2).'%',
                "{{$key}.pct}" => (string) round($plan['insurance_token_rate'] * 100, 2),
            ])->all();
    }

    private function page(string $slug): array
    {
        return $this->values[$slug] ??= array_merge(
            self::defaults($slug),
            Cache::rememberForever("cms.{$slug}", fn () => Schema::hasTable('pages') ? (Page::where('slug', $slug)->value('content') ?? []) : []),
        );
    }
}
