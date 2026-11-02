<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Policy;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Notifications\MemberAlert;
use App\Services\MemberBillingService;
use App\Services\TokenLedger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/** SOW: Insurance Policy Management — multiple policies per member, renewal data, verification. */
class PolicyResource extends Resource
{
    public const TYPES = ['Auto', 'Home', 'Renters', 'Condo', 'Life', 'Health', 'Commercial', 'Umbrella', 'Other'];

    public static string $model = Policy::class;

    public static string $label = 'Policies';

    public static string $singular = 'Policy';

    public static string $group = 'Insurance';

    public static array $search = ['policy_number', 'insured_name', 'carrier', 'user.email'];

    public static bool $creatable = true;

    public static bool $editable = true;

    public function title(Model $p): string
    {
        return "{$p->policy_number} · {$p->type}";
    }

    public function query(): Builder
    {
        return Policy::with('user')->latest('id');
    }

    public function columns(): array
    {
        return [
            ['key' => 'policy_number', 'label' => 'Policy'],
            ['key' => 'type', 'label' => 'Type'],
            ['key' => 'user.name', 'label' => 'Member'],
            ['key' => 'annual_premium', 'label' => 'Annual premium', 'type' => 'money'],
            ['key' => 'expires_on', 'label' => 'Expires', 'type' => 'date'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
        ];
    }

    public function filters(): array
    {
        return [
            'status' => ['label' => 'Status', 'options' => self::options(['active', 'pending', 'cancelled', 'expired'])],
            'type' => ['label' => 'Type', 'options' => array_combine(self::TYPES, self::TYPES)],
            'renewal' => ['label' => 'Renewal', 'options' => ['30' => 'Expiring in 30 days', '90' => 'Expiring in 90 days'],
                'scope' => fn ($q, $v) => $q->where('status', 'active')->whereBetween('expires_on', [today(), today()->addDays((int) $v)])],
        ];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'member_email', 'label' => 'Member email', 'type' => 'email', 'hint' => 'The registered member who holds this policy.'],
            ['name' => 'type', 'label' => 'Type', 'type' => 'select', 'options' => array_combine(self::TYPES, self::TYPES), 'half' => true],
            ['name' => 'policy_number', 'label' => 'Policy number', 'half' => true, 'hint' => 'Format CSP-REN-000123 (code matches the type). Leave blank to number it automatically.'],
            ['name' => 'carrier', 'label' => 'Carrier', 'half' => true],
            ['name' => 'insured_name', 'label' => 'Insured name', 'half' => true],
            ['name' => 'covers', 'label' => 'Insured address / vehicles / plan'],
            ['name' => 'starts_on', 'label' => 'Effective date', 'type' => 'date', 'half' => true],
            ['name' => 'expires_on', 'label' => 'Expiration date', 'type' => 'date', 'half' => true],
            ['name' => 'annual_premium', 'label' => 'Annual premium ($)', 'type' => 'number', 'half' => true],
            ['name' => 'amount_due', 'label' => 'Amount due now ($)', 'type' => 'number', 'half' => true],
            ['name' => 'due_on', 'label' => 'Due date', 'type' => 'date', 'half' => true],
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::options(['active', 'pending', 'expired']), 'half' => true],
            ['name' => 'renewal_note', 'label' => 'Renewal note', 'hint' => 'e.g. Auto-renews, Renewal due'],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'member_email' => ['required', 'email', Rule::exists('users', 'email')],
            'type' => ['required', Rule::in(self::TYPES)],
            'policy_number' => ['nullable', 'string', 'max:60', Rule::unique('policies')->ignore($model?->id),
                // An existing policy may keep a number it already had; new or changed numbers use the COSPARK format
                function (string $attribute, mixed $value, \Closure $fail) use ($model) {
                    $number = strtoupper(trim((string) $value));
                    if ($number === '' || ($model && $number === strtoupper((string) $model->policy_number))) {
                        return;
                    }
                    $prefix = Policy::numberPrefix((string) request('type'));
                    if (! preg_match(Policy::NUMBER_PATTERN, $number) || ! str_starts_with($number, $prefix)) {
                        $fail("Use the format {$prefix}000123 for this policy type, or leave it blank to number it automatically.");
                    }
                }],
            'carrier' => ['required', 'string', 'max:120'], 'insured_name' => ['required', 'string', 'max:160'],
            'covers' => ['nullable', 'string', 'max:255'],
            'starts_on' => ['required', 'date'], 'expires_on' => ['required', 'date', 'after:starts_on'],
            'annual_premium' => ['required', 'numeric', 'min:0'], 'amount_due' => ['required', 'numeric', 'min:0'],
            'due_on' => ['nullable', 'date'], 'status' => ['required', Rule::in(['active', 'pending', 'expired'])],
            'renewal_note' => ['nullable', 'string', 'max:120'],
        ];
    }

    public function save(Model $policy, array $data): Model
    {
        $user = User::where('email', strtolower($data['member_email']))->firstOrFail();
        if ($user->isSubAccount()) {
            throw ValidationException::withMessages(['member_email' => 'Add policies to the main member.']);
        }
        $new = ! $policy->exists;
        $data['policy_number'] = strtoupper(trim((string) ($data['policy_number'] ?? ''))) ?: ($policy->policy_number ?: Policy::nextNumber($data['type']));
        $policy->fill(collect($data)->except('member_email')->all() + ['user_id' => $user->id])->save();
        if ($new) {
            $user->notify(new MemberAlert('Policy', "{$policy->type} policy added", "{$policy->policy_number} · {$policy->carrier}", 'policies', mail: true));
        }

        return $policy;
    }

    public function detail(Model $p): array
    {
        return [
            ['title' => 'Policy', 'rows' => [
                ['Policy number', $p->policy_number], ['Member', $p->user->name.' · '.$p->user->email], ['Type', $p->type], ['Carrier', $p->carrier], ['Insured', $p->insured_name], ['Covers', $p->covers],
                ['Term', $p->starts_on->format('M j, Y').' – '.$p->expires_on->format('M j, Y')], ['Annual premium', '$'.number_format($p->annual_premium, 2)],
                ['Amount due', '$'.number_format($p->amount_due, 2).($p->due_on ? ' · '.$p->due_on->format('M j, Y') : '')],
                ['Status', $this->format($p->status, 'pill')], ['Renewal', $p->renewal_note], ['Policy year (IT)', $p->policyYear()],
            ]],
            $this->table('Documents', ['Name', 'Added'], $p->documents->map(fn ($d) => [$d->name, $d->created_at->format('M j, Y')])),
        ];
    }

    public function actions(Model $p): array
    {
        return array_values(array_filter([
            ['key' => 'document', 'label' => 'Upload document', 'style' => 'line', 'fields' => [
                ['name' => 'name', 'label' => 'Document name', 'rules' => ['required', 'string', 'max:120']],
                ['name' => 'file', 'label' => 'File', 'type' => 'file', 'rules' => ['required', 'file', 'max:'.config('cospark.upload_max_kb'), 'mimes:pdf,jpg,jpeg,png,doc,docx']],
            ]],
            $p->status === 'active' ? ['key' => 'premium', 'label' => 'Record premium payment', 'style' => 'line', 'fields' => [
                ['name' => 'amount', 'label' => 'Amount paid', 'type' => 'number', 'default' => (float) $p->amount_due ?: (float) $p->annual_premium, 'rules' => ['required', 'numeric', 'min:0.01', 'max:10000000']],
                ['name' => 'paid_on', 'label' => 'Paid on', 'type' => 'date', 'default' => today()->toDateString(), 'rules' => ['required', 'date', 'before_or_equal:today']],
                ['name' => 'reference', 'label' => 'Carrier receipt / reference', 'rules' => ['nullable', 'string', 'max:120']],
            ]] : null,
            $p->status === 'active' ? ['key' => 'cancel', 'label' => 'Cancel policy', 'style' => 'danger', 'confirm' => "This year's Insurance Tokens for this policy are forfeited; completed years remain."] : null,
        ]));
    }

    public function runAction(Model $p, string $action, array $data): string
    {
        return match ($action) {
            'premium' => tap('Premium payment recorded.', function () use ($p, $data) {
                $payment = app(MemberBillingService::class)->recordPremium($p, (float) $data['amount'], $data['paid_on'], $data['reference'] ?? null);
                $tokens = (int) TokenTransaction::where('payment_id', $payment->id)->where('token_type', TokenTransaction::INSURANCE)->sum('amount');
                $p->user->notify(new MemberAlert('Policy', 'Premium payment recorded', '$'.number_format($payment->amount, 2)." · {$p->policy_number}".($tokens ? " · {$tokens} IT credited" : ''), 'policies', mail: true));
            }),
            'document' => tap('Document added.', function () use ($p, $data) {
                $p->documents()->create(['name' => $data['name'], 'path' => $data['file']->store("policies/{$p->id}")]);
                $p->user->notify(new MemberAlert('Policy', 'New policy document', "{$data['name']} · {$p->policy_number}", 'policies'));
            }),
            'cancel' => tap('Policy cancelled and current-year IT forfeited.', function () use ($p) {
                app(TokenLedger::class)->forfeitCurrentYear($p);
                $p->update(['status' => 'cancelled', 'cancelled_at' => now(), 'amount_due' => 0]);
            }),
        };
    }
}
