<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Claim;
use App\Models\Lead;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Policy;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Models\VacationRequest;
use App\Models\VacationTokenLot;
use App\Support\QrSvg;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Builds the member dashboard payload consumed by public/assets/js/dashboard.js.
 * Dates are ISO (Y-m-d); labels match the approved prototype wording.
 */
class MemberDashboardPresenter
{
    private const PAYMENT_KINDS = [
        Payment::TYPE_DUES => 'Membership dues',
        Payment::TYPE_PREMIUM => 'Insurance premium',
        Payment::TYPE_VACATION_TOKENS => 'Vacation Tokens',
        Payment::TYPE_SUB_ACCOUNT => 'Membership dues',
    ];

    private const PAYMENT_STATUS = ['succeeded' => 'Successful', 'failed' => 'Failed', 'pending' => 'Pending', 'refunded' => 'Refunded', 'cancelled' => 'Cancelled'];

    private const QUOTE_STATUS = ['new' => 'Quote requested', 'contacted' => 'Agent contacted', 'quoted' => 'Quote ready', 'lost' => 'Closed'];

    private const LEDGER_TYPES = [
        'earned' => 'Earned', 'purchased' => 'Purchased', 'accrued' => 'Accrued', 'used' => 'Used',
        'transferred_in' => 'Received', 'transferred_out' => 'Transferred', 'forfeited' => 'Forfeited', 'adjusted' => 'Adjusted',
        'refunded' => 'Refunded',
    ];

    public function present(User $user): array
    {
        $user->loadMissing('membership', 'parent');
        $owner = $user->isSubAccount() ? $user->parent : $user;
        $canSeePolicies = ! $user->isSubAccount() || $user->hasPermission('View policies');

        return [
            'plan' => $user->isSubAccount() ? 'sub' : $this->effectivePlan($user),
            'plans' => $this->plans(),
            'user' => $this->user($user),
            'member' => $this->member($user),
            'policies' => $canSeePolicies ? $owner->policies()->orderBy('expires_on')->get()->map(fn (Policy $p) => $this->policy($p))->all() : [],
            'quotes' => $canSeePolicies ? $this->quotes($owner) : [],
            'methods' => $user->paymentMethods()->orderByDesc('is_default')->oldest()->get()->map(fn (PaymentMethod $m) => $this->method($m))->all(),
            'payments' => $user->payments()->latest()->latest('id')->get()->map(fn (Payment $p) => $this->payment($p))->all(),
            'ledger' => $user->tokenTransactions()->with('policy')->latest()->latest('id')->get()->map(fn (TokenTransaction $t) => $this->ledgerEntry($t))->all(),
            'vt' => $user->vacationTokenLots()->where('status', 'active')->oldest()->get()->map(fn (VacationTokenLot $l) => $this->lot($l))->all(),
            'claims' => $this->claims($user),
            'vacations' => $user->vacationRequests()->with('partner')->latest()->get()->map(fn (VacationRequest $v) => $this->vacation($v))->all(),
            'appts' => $this->appointments($user),
            'notes' => self::notifications($user, 'member'),
            'subs' => $user->subAccounts()->oldest()->get()->map(fn (User $s) => $this->subAccount($s))->all(),
            'redemptions' => $user->qrRedemptions()->with('partner')->latest()->get()->map(fn ($r) => [
                'date' => $r->created_at->toDateString(),
                'partner' => trim(($r->partner?->name ?? 'Partner').($r->location ? " · {$r->location}" : '')),
                'amt' => $r->amount,
                'ref' => $r->reference,
            ])->all(),
            'properties' => Partner::active()->where('is_vacation', true)->get()->map(fn (Partner $p) => $this->property($p))->values()->all(),
            'partners' => Partner::active()->get()->map(fn (Partner $p) => ['n' => $p->name, 'c' => $p->category, 'd' => $p->description, 'img' => $p->image_url])->values()->all(),
            'qr' => $user->qrCode ? ['code' => $user->qrCode->code, 'status' => Str::title($user->qrCode->status), 'svg' => QrSvg::make($user->qrCode->code)] : null,
            'config' => [
                'transferEmail' => config('cospark.office.token_transfer_email'),
                'vtMin' => config('membership.vacation_token_minimum'),
                'noticeDays' => VacationRequest::MIN_NOTICE_DAYS,
                'maxGuests' => config('membership.vacations.max_guests'),
                'subFee' => config('membership.sub_account_fee'),
                'subPermissions' => config('membership.sub_account_permissions'),
                'producerDiscount' => $user->producer?->isActive() ? \App\Models\Producer::VACATION_DISCOUNT : 0,
                'isProducer' => (bool) $user->producer?->isActive(),
                'freqs' => ['associate' => array_keys(config('membership.frequencies')), 'premium' => array_keys(config('membership.frequencies'))],
            ],
        ];
    }

    /** Notification centre entries for one portal ("member" or "producer"), newest first. */
    public static function notifications(User $user, string $portal): array
    {
        return $user->notifications()->latest()->limit(200)->get()
            ->filter(fn ($n) => ($n->data['portal'] ?? 'member') === $portal)
            ->take(100)
            ->map(fn ($n) => ['id' => $n->id, ...$n->data, 'date' => $n->created_at->toDateString(), 'read' => $n->read_at !== null])
            ->values()
            ->all();
    }

    private function plans(): array
    {
        $plans = collect(config('membership.plans'))
            ->put('sub', config('membership.sub_account_plan'))
            ->map(fn ($p) => ['n' => $p['name'], 'fee' => (float) $p['monthly_fee'], 'dt' => $p['dues_tokens'], 'it' => $p['insurance_token_rate']]);

        return $plans->all();
    }

    private function user(User $user): array
    {
        return [
            'first' => $user->first_name, 'last' => $user->last_name, 'email' => $user->email, 'phone' => $user->phone,
            'street' => $user->street, 'street2' => $user->street2, 'city' => $user->city, 'st' => $user->state, 'zip' => $user->zip,
            'sms' => $user->sms_opt_in,
            'avatar' => $user->avatar_path ? Storage::disk('public')->url($user->avatar_path) : null,
        ];
    }

    private function member(User $user): array
    {
        $m = $user->membership;
        $default = $user->paymentMethods()->where('is_default', true)->first();

        $status = match (true) {
            $m === null => 'Active',
            $m->cancelled_at !== null && $m->current_period_end?->isFuture() => 'Cancels '.$m->current_period_end->format('M j, Y'),
            default => Str::title($m->status),
        };

        return [
            'id' => $user->member_number,
            'start' => ($m?->started_at ?? $user->created_at)->toDateString(),
            'freq' => config('membership.frequencies.'.($m?->billing_frequency ?? 'monthly').'.label'),
            'freqKey' => $m?->billing_frequency ?? 'monthly',
            'status' => $status,
            'next' => $m?->current_period_end?->toDateString(),
            'periodAmount' => $m?->periodAmount() ?? 0,
            'nextChargeAmount' => $m && $m->isPaid() && $m->status === 'active' && $m->auto_renew && ! $m->cancelled_at && $m->pending_plan !== 'free'
                ? (float) config('membership.plans.'.($m->pending_plan ?? $m->plan).'.monthly_fee') * $m->frequencyMonths() : 0,
            'nextChargeDate' => $m && $m->status === 'active' && $m->auto_renew && ! $m->cancelled_at && $m->pending_plan !== 'free'
                ? $m->current_period_end?->toDateString() : null,
            'autopay' => (bool) ($m?->auto_renew && $default),
            'methodLabel' => $default?->label(),
            'pendingPlan' => $m?->pending_plan,
            'cancelled' => $m?->cancelled_at !== null,
            'parentName' => $user->parent?->name,
            'perms' => $user->isSubAccount() ? ($user->permissions ?? []) : null,
            // Billing state for renew / pay-now prompts
            'lapsedPlan' => $m && $m->isPaid() && in_array($m->status, ['expired', 'cancelled'], true) ? $m->plan : null,
            'expiredAt' => $m?->expired_at?->toDateString(),
            'due' => $m && $m->isPaid() && $m->status === 'active' && $m->billing_status === 'past_due',
        ];
    }

    /** Paid plans count only while the membership is active; expired / cancelled members are on Free. */
    private function effectivePlan(User $user): string
    {
        $m = $user->membership;

        return $m && $m->status === 'active' ? $m->plan : 'free';
    }

    private function policy(Policy $p): array
    {
        return [
            'id' => $p->id, 'type' => $p->type, 'icon' => $p->icon(), 'no' => $p->policy_number, 'carrier' => $p->carrier,
            'insured' => $p->insured_name, 'addr' => $p->covers ?? '—', 'start' => $p->starts_on->toDateString(), 'exp' => $p->expires_on->toDateString(),
            'premium' => (float) $p->annual_premium, 'due' => $p->due_on?->toDateString(), 'amountDue' => (float) $p->amount_due,
            'paid' => $p->amount_due <= 0, 'status' => Str::title($p->status), 'renewal' => $p->renewal_note ?? '—',
            'docs' => $p->documents()->get()->map(fn ($d) => ['id' => $d->id, 'name' => $d->name, 'date' => $d->updated_at->toDateString(), 'url' => route('dashboard.policies.documents', [$p, $d])])->all(),
        ];
    }

    private function method(PaymentMethod $m): array
    {
        return [
            'id' => $m->id, 'brand' => $m->brand, 'bank' => $m->isBank() ? ($m->bank_name ?: 'Bank account') : null,
            'last4' => $m->last4, 'exp' => $m->expiryLabel(), 'def' => $m->is_default, 'expired' => $m->isExpired(),
        ];
    }

    private function payment(Payment $p): array
    {
        return [
            'ref' => $p->reference, 'date' => ($p->paid_at ?? $p->created_at)->toDateString(),
            'kind' => self::PAYMENT_KINDS[$p->type] ?? Str::title(str_replace('_', ' ', $p->type)),
            'desc' => $p->description ?? '—', 'amt' => (float) $p->amount,
            'status' => self::PAYMENT_STATUS[$p->status] ?? Str::title($p->status),
            'method' => $p->method_label ?? '—',
            'retryable' => $p->status === 'failed' && in_array($p->type, [Payment::TYPE_DUES, Payment::TYPE_PREMIUM, Payment::TYPE_VACATION_TOKENS], true),
        ];
    }

    private function ledgerEntry(TokenTransaction $t): array
    {
        return [
            'date' => $t->created_at->toDateString(), 'tok' => $t->token_type, 'type' => self::LEDGER_TYPES[$t->kind] ?? Str::title($t->kind),
            'amt' => (float) $t->amount, 'desc' => $t->description, 'rel' => $t->reference ?? '—',
            'policy' => $t->policy?->type, 'py' => $t->policy_year,
            'state' => match ($t->status) {
                'forfeited' => 'Forfeited', 'pending' => 'Pending review', default => 'Current year'
            },
        ];
    }

    private function lot(VacationTokenLot $l): array
    {
        return [
            'lot' => $l->lot_number, 'date' => $l->purchased_on->toDateString(), 'orig' => (float) $l->face_value,
            'used' => (float) $l->used_value, 'accrued' => $l->accruedValue(),
        ];
    }

    private function claims(User $user): array
    {
        return Claim::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere(fn ($q) => $q->whereNull('user_id')->where('email', $user->email)))
            ->with('messages')
            ->latest()
            ->get()
            ->map(fn (Claim $c) => [
                'no' => $c->reference, 'type' => $c->insurance_type, 'policy' => $c->policy_number,
                'sub' => $c->created_at->toDateString(), 'inc' => $c->incident_date->toDateString(),
                'status' => Str::title(str_replace('_', ' ', $c->status)), 'upd' => $c->updated_at->toDateString(),
                'action' => $c->required_action ?: 'None', 'docs' => $c->outstanding_documents ?? [], 'step' => $c->progress_step,
                'msgs' => $c->messages->map(fn ($m) => [$m->from_member ? 'You' : $m->author_name, $m->body, $m->created_at->toDateString()])->all(),
            ])->all();
    }

    /** Open quote requests (Get a Quote) shown on My Policies until the policy is issued. */
    private function quotes(User $owner): array
    {
        return Lead::query()
            ->where(fn ($q) => $q->where('user_id', $owner->id)->orWhere(fn ($q) => $q->whereNull('user_id')->where('email', $owner->email)))
            ->where('status', '!=', 'won')
            ->latest()
            ->get()
            ->map(fn (Lead $l) => [
                'ref' => $l->reference, 'type' => $l->interestLabel(), 'date' => $l->created_at->toDateString(),
                'status' => self::QUOTE_STATUS[$l->status] ?? Str::title($l->status),
            ])->all();
    }

    private function vacation(VacationRequest $v): array
    {
        return [
            'id' => $v->reference, 'prop' => $v->partner->name, 'loc' => $v->partner->location, 'in' => $v->check_in->toDateString(),
            'out' => $v->check_out->toDateString(), 'guests' => $v->guests, 'req' => $v->created_at->toDateString(),
            'tokens' => $v->tokens_applied, 'pay' => (float) $v->payment_amount,
            'payStatus' => Str::title(str_replace('_', ' ', $v->payment_status)), 'status' => Str::title($v->status),
            'notes' => $v->notes, 'instructions' => $v->instructions, 'cancellable' => $v->isCancellable(),
        ];
    }

    private function appointments(User $user): array
    {
        return Appointment::query()
            ->where(fn ($q) => $q->where('user_id', $user->id)->orWhere('email', $user->email))
            ->orderByDesc('preferred_date')
            ->get()
            ->map(fn (Appointment $a) => [
                'id' => $a->reference, 'type' => $a->type_label, 'date' => $a->preferred_date->toDateString(), 'time' => $a->preferred_time,
                'with' => 'COSPARK office', 'reason' => $a->reason ?? 'Appointment', 'status' => $a->status === 'pending' ? 'Requested' : Str::title($a->status),
            ])->all();
    }

    private function subAccount(User $s): array
    {
        return [
            'id' => $s->id, 'name' => $s->name, 'email' => $s->email, 'rel' => $s->relationship ?? '—',
            'status' => match ($s->status) {
                'pending' => 'Invited', 'suspended' => 'Deactivated', default => 'Active'
            },
            'since' => $s->created_at->toDateString(), 'perms' => $s->permissions ?? [],
        ];
    }

    private function property(Partner $p): array
    {
        return [
            'id' => $p->id, 'n' => $p->name, 'loc' => $p->location, 'd' => $p->description, 'img' => $p->image_url, 'amen' => $p->amenities ?? [],
            'rate' => (float) ($p->nightly_rate ?? config('membership.vacations.default_nightly_rate')),
            'tpn' => (int) ($p->tokens_per_night ?? config('membership.vacations.default_tokens_per_night')),
        ];
    }
}
