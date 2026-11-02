<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\User;
use App\Services\BenefitsService;
use App\Services\TokenLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** SOW: User Management — view, search, filter, edit, activate/deactivate, history. */
class UserResource extends Resource
{
    public static string $model = User::class;

    public static string $label = 'Users';

    public static string $singular = 'User';

    public static string $group = 'Members';

    public static array $search = ['first_name', 'last_name', 'email', 'phone'];

    public static bool $creatable = true;

    public static bool $editable = true;

    public function query(): Builder
    {
        return User::with('membership', 'parent')->latest('id');
    }

    public function columns(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'role', 'label' => 'Role', 'type' => 'pill'],
            ['key' => 'membership.plan', 'label' => 'Plan', 'type' => 'pill'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'created_at', 'label' => 'Registered', 'type' => 'date'],
        ];
    }

    public function filters(): array
    {
        return [
            'role' => ['label' => 'Role', 'options' => self::options(['member', 'producer', 'admin'])],
            'status' => ['label' => 'Status', 'options' => self::options(['active', 'pending', 'suspended'])],
            'plan' => ['label' => 'Plan', 'options' => self::options(['free', 'associate', 'premium', 'sub_account']),
                'scope' => fn ($q, $v) => $q->whereHas('membership', fn ($m) => $m->where('plan', $v))],
        ];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'first_name', 'label' => 'First name', 'half' => true],
            ['name' => 'last_name', 'label' => 'Last name', 'half' => true],
            ['name' => 'email', 'label' => 'Email', 'type' => 'email', 'half' => true],
            ['name' => 'phone', 'label' => 'Phone', 'half' => true],
            ['name' => 'street', 'label' => 'Street'],
            ['name' => 'city', 'label' => 'City', 'half' => true],
            ['name' => 'state', 'label' => 'State', 'type' => 'select', 'options' => array_combine(config('cospark.us_states'), config('cospark.us_states')), 'half' => true],
            ['name' => 'zip', 'label' => 'ZIP', 'half' => true],
            ['name' => 'role', 'label' => 'Role', 'type' => 'select', 'options' => self::options($model?->producer ? ['member', 'producer', 'admin'] : ['member', 'admin']), 'half' => true, 'hint' => 'Onboard producers by approving their application.'],
            ...($model ? [] : [['name' => 'password', 'label' => 'Temporary password', 'type' => 'text', 'hint' => 'Share it securely; the user can reset it.']]),
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'], 'last_name' => ['required', 'string', 'max:80'],
            'email' => ['required', 'email:filter', Rule::unique('users')->ignore($model?->id)],
            'phone' => ['nullable', 'string', 'max:40', new \App\Rules\PhoneNumber], 'street' => ['nullable', 'string', 'max:255'], 'city' => ['nullable', 'string', 'max:120'],
            'state' => ['nullable', Rule::in(config('cospark.us_states'))], 'zip' => ['nullable', 'string', 'max:10'],
            'role' => ['required', Rule::in(['member', 'producer', 'admin']), function ($attribute, $value, $fail) use ($model) {
                if ($value === 'producer' && ! $model?->producer) {
                    $fail('Approve and onboard a producer application before assigning the Producer role.');
                }
            }],
            'password' => [$model ? 'nullable' : 'required', 'string', 'min:8'],
        ];
    }

    public function save(Model $model, array $data): Model
    {
        $new = ! $model->exists;
        $model->fill(array_filter($data, fn ($v) => $v !== null))->forceFill(['email_verified_at' => $model->email_verified_at ?? now()]);
        $model->status ??= 'active';
        $model->save();
        if ($new) {
            $model->memberships()->create(['plan' => 'free', 'monthly_fee' => 0, 'status' => 'active', 'started_at' => now()]);
        }

        return $model;
    }

    public function detail(Model $user): array
    {
        $balances = app(TokenLedger::class)->balances($user);
        $m = $user->membership;

        return [
            ['title' => 'Account', 'rows' => [
                ['Name', $user->name], ['Member ID', $user->member_number], ['Email', $user->email], ['Phone', $user->phone],
                ['Address', trim("{$user->street}, {$user->city} {$user->state} {$user->zip}", ' ,')], ['Role', $this->format($user->role, 'pill')],
                ['Status', $this->format($user->status, 'pill')], ['SMS consent', $user->sms_opt_in ? 'Yes · '.$user->sms_opt_in_at?->format('M j, Y') : 'No'],
                ['Main member', $user->parent?->name], ['Registered', $user->created_at->format('M j, Y')],
            ]],
            ['title' => 'Membership & tokens', 'rows' => [
                ['Plan', $this->format($m?->plan, 'pill')], ['Status', $this->format($m?->status, 'pill')], ['Paid through', $m?->current_period_end?->format('M j, Y')],
                ['DT', $balances['DT']], ['IT', $balances['IT']], ['VT', '$'.number_format($balances['VT'], 2)], ['QR code', $this->format($user->qrCode?->status, 'pill')], ['QR eligibility', app(\App\Services\BenefitsService::class)->qrIneligibility($user) ?? 'Eligible'],
            ]],
            $this->table('Payments', ['Date', 'Reference', 'Description', 'Amount', 'Status'], $user->payments()->latest()->limit(15)->get()
                ->map(fn ($p) => [$p->created_at->format('M j, Y'), $p->reference, $p->description, '$'.number_format($p->amount, 2), $this->format($p->status, 'pill')])),
            $this->table('Policies', ['Policy', 'Type', 'Expires', 'Premium', 'Status'], $user->policies
                ->map(fn ($p) => [$p->policy_number, $p->type, $p->expires_on->format('M j, Y'), '$'.number_format($p->annual_premium, 2), $this->format($p->status, 'pill')])),
            $this->table('Appointments', ['Date', 'Time', 'Type', 'Reason', 'Status'], $user->appointments()->latest('preferred_date')->limit(10)->get()
                ->map(fn ($a) => [$a->preferred_date->format('M j, Y'), $a->preferred_time, $a->type_label, $a->reason, $this->format($a->status, 'pill')])),
        ];
    }

    public function actions(Model $user): array
    {
        return [
            $user->is(auth()->user()) ? null : ($user->status === 'suspended'
                ? ['key' => 'activate', 'label' => 'Activate account', 'style' => 'copper']
                : ['key' => 'suspend', 'label' => 'Deactivate account', 'style' => 'danger', 'confirm' => 'The user will not be able to sign in.']),
            ['key' => 'adjust', 'label' => 'Adjust tokens', 'style' => 'line', 'fields' => [
                ['name' => 'type', 'label' => 'Token type', 'type' => 'select', 'options' => ['DT' => 'DT', 'IT' => 'IT', 'VT' => 'VT'], 'rules' => ['required', Rule::in(['DT', 'IT', 'VT'])]],
                ['name' => 'amount', 'label' => 'Amount (negative to deduct)', 'type' => 'number', 'rules' => ['required', 'numeric', 'not_in:0']],
                ['name' => 'reason', 'label' => 'Reason (kept in the audit trail)', 'rules' => ['required', 'string', 'max:160']],
            ]],
            app(\App\Services\BenefitsService::class)->qrIneligibility($user) ? null : ['key' => 'qr', 'label' => $user->qrCode ? 'Reissue QR code' : 'Generate QR code', 'style' => 'line',
                'confirm' => 'Eligible · '.ucfirst((string) $user->membership?->plan).' membership active. Any existing QR code for this member stops working.'],
        ];
    }

    public function runAction(Model $user, string $action, array $data): string
    {
        $benefits = app(BenefitsService::class);

        return match ($action) {
            'activate' => tap('Account activated.', fn () => $user->update(['status' => 'active'])),
            'suspend' => tap('Account deactivated.', fn () => $user->update(['status' => 'suspended'])),
            'adjust' => tap('Tokens adjusted.', fn () => $benefits->adjustTokens($user, $data['type'], (float) $data['amount'], $data['reason'].' · by '.auth()->user()->name)),
            'qr' => tap('QR code issued.', fn () => $benefits->issueQr($user)),
        };
    }
}
