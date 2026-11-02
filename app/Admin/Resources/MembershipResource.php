<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Membership;
use App\Services\MembershipService;
use App\Services\TokenLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** SOW: Membership Management — status, billing, cancellations (business rules via MembershipService). */
class MembershipResource extends Resource
{
    public static string $model = Membership::class;

    public static string $label = 'Memberships';

    public static string $singular = 'Membership';

    public static string $group = 'Members';

    public static array $search = ['user.email', 'user.last_name'];

    public function query(): Builder
    {
        return Membership::with('user')->latest('id');
    }

    public function columns(): array
    {
        return [
            ['key' => 'user.name', 'label' => 'Member'],
            ['key' => 'plan', 'label' => 'Plan', 'type' => 'pill'],
            ['key' => 'billing_frequency', 'label' => 'Billing', 'type' => 'pill'],
            ['key' => 'monthly_fee', 'label' => 'Fee / mo', 'type' => 'money'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'current_period_end', 'label' => 'Paid through', 'type' => 'date'],
            ['key' => 'journey_label', 'label' => '60-month journey'],
        ];
    }

    public function filters(): array
    {
        return [
            'plan' => ['label' => 'Plan', 'options' => self::options(['free', 'associate', 'premium', 'sub_account'])],
            'status' => ['label' => 'Status', 'options' => self::options(['active', 'pending', 'expired', 'cancelled'])],
            // started_at is set for every activated membership; the 60-month cycle counts from it
            'redemption' => ['label' => '60-month redemption', 'options' => ['eligible' => 'Eligible now', 'within_12' => 'Eligible within 12 months', 'in_progress' => 'In progress'], 'scope' => fn (Builder $q, string $v) => match ($v) {
                'eligible' => $q->where('started_at', '<=', now()->subMonths(Membership::REDEMPTION_MONTHS)),
                'within_12' => $q->whereBetween('started_at', [now()->subMonths(Membership::REDEMPTION_MONTHS), now()->subMonths(Membership::REDEMPTION_MONTHS - 12)]),
                'in_progress' => $q->where('started_at', '>', now()->subMonths(Membership::REDEMPTION_MONTHS)),
            }],
        ];
    }

    public function title(Model $m): string
    {
        return $m->user->name.' · '.$this->format($m->plan, 'pill');
    }

    public function detail(Model $m): array
    {
        return [
            ['title' => 'Membership', 'rows' => [
                ['Member', $m->user->name.' · '.$m->user->email], ['Plan', $this->format($m->plan, 'pill')], ['Scheduled change', $this->format($m->pending_plan, 'pill')],
                ['Billing', $this->format($m->billing_frequency, 'pill')], ['Fee / month', '$'.number_format($m->monthly_fee, 2)], ['Status', $this->format($m->status, 'pill')],
                ['Started', $m->started_at?->format('M j, Y')], ['Paid through', $m->current_period_end?->format('M j, Y')], ['Auto-renew', $m->auto_renew ? 'Yes' : 'No'],
                ['Cancellation requested', $m->cancelled_at?->format('M j, Y')], ['Billing status', $this->format($m->billing_status, 'pill')],
            ]],
            ['title' => '60-month redemption', 'rows' => [
                ['Journey', $m->journey_label], ['Counted from', $m->journeyStart()?->format('M j, Y')],
                ['Redemption eligible on', $m->redemptionEligibleOn()?->format('M j, Y')], ['Eligible now', $m->isRedemptionEligible() ? 'Yes' : 'No'],
                ['Available tokens', collect(app(TokenLedger::class)->balances($m->user))->map(fn ($v, $t) => $t.' '.number_format($v))->implode(' · ')],
            ]],
            $this->table('Dues payments', ['Date', 'Reference', 'Amount', 'Period', 'Status'], $m->payments()->latest()->get()
                ->map(fn ($p) => [$p->created_at->format('M j, Y'), $p->reference, '$'.number_format($p->amount, 2), $p->period_start?->format('M j').' – '.$p->period_end?->format('M j, Y'), $this->format($p->status, 'pill')])),
        ];
    }

    public function actions(Model $m): array
    {
        if (! $m->isPaid() || $m->status !== 'active' || $m->plan === 'sub_account') {
            return [];
        }

        return [
            $m->cancelled_at ? null : ['key' => 'cancel', 'label' => 'Cancel at period end', 'style' => 'line', 'confirm' => 'Benefits continue until the paid period ends.'],
            ['key' => 'end', 'label' => 'End membership now', 'style' => 'danger', 'confirm' => 'Paid benefits stop immediately and the membership is not renewed. No refund is issued automatically.'],
        ];
    }

    public function runAction(Model $m, string $action, array $data): string
    {
        $memberships = app(MembershipService::class);

        return match ($action) {
            'cancel' => tap('Membership will end at the period end.', fn () => $memberships->cancel($m)),
            'end' => tap('Membership ended.', fn () => $memberships->expire($m)),
        };
    }
}
