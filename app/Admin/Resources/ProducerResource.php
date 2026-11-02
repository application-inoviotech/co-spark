<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Producer;
use App\Notifications\MemberAlert;
use App\Services\ProducerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;

/** Producers: licensing & document verification, commissions, bonus, payout and program details. */
class ProducerResource extends Resource
{
    public static string $model = Producer::class;

    public static string $label = 'Producers';

    public static string $singular = 'Producer';

    public static string $group = 'Producers';

    public static array $search = ['producer_number', 'npn', 'business_name', 'user.email', 'user.last_name'];

    public static bool $editable = true;

    public function query(): Builder
    {
        return Producer::with('user', 'licenses')->latest('id');
    }

    public function title(Model $p): string
    {
        return $p->user->name.' · '.$p->producer_number;
    }

    public function columns(): array
    {
        return [
            ['key' => 'producer_number', 'label' => 'Producer ID'],
            ['key' => 'user.name', 'label' => 'Name'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'bonus_status', 'label' => 'Bonus', 'type' => 'pill'],
            ['key' => 'onboarded_on', 'label' => 'Onboarded', 'type' => 'date'],
        ];
    }

    public function filters(): array
    {
        return ['status' => ['label' => 'Status', 'options' => self::options(['active', 'under_review', 'suspended'])]];
    }

    public function fields(?Model $model): array
    {
        return [
            ['name' => 'status', 'label' => 'Status', 'type' => 'select', 'options' => self::options(['active', 'under_review', 'suspended']), 'half' => true],
            ['name' => 'npn', 'label' => 'NPN', 'half' => true],
            ['name' => 'business_name', 'label' => 'Business name'],
            ['name' => 'business_address', 'label' => 'Business address'],
            ['name' => 'commission_rate', 'label' => 'Commission rate (0.75 = 75%)', 'type' => 'number', 'half' => true],
            ['name' => 'payout_label', 'label' => 'Payout account (display)', 'half' => true],
            ['name' => 'auto_allowance_status', 'label' => 'Auto allowance', 'type' => 'select', 'options' => self::options(['in_progress', 'qualified', 'not_eligible']), 'half' => true],
        ];
    }

    public function rules(?Model $model): array
    {
        return [
            'status' => ['required', Rule::in(['active', 'under_review', 'suspended'])], 'npn' => ['nullable', 'string', 'max:20'],
            'business_name' => ['nullable', 'string', 'max:160'], 'business_address' => ['nullable', 'string', 'max:255'],
            'commission_rate' => ['required', 'numeric', 'between:0,1'], 'payout_label' => ['nullable', 'string', 'max:60'],
            'auto_allowance_status' => ['required', Rule::in(['in_progress', 'qualified', 'not_eligible'])],
        ];
    }

    public function save(Model $producer, array $data): Model
    {
        if ($producer->status !== 'active' && $data['status'] === 'active') {
            $data += ['onboarded_on' => $producer->onboarded_on ?? today(), 'qualification_started_on' => $producer->qualification_started_on ?? today()];
        }
        $producer->update($data);
        app(ProducerService::class)->syncBonus($producer);

        return $producer;
    }

    public function detail(Model $p): array
    {
        $e = $p->bonusEligibility();

        return [
            ['title' => 'Producer', 'rows' => [
                ['Name', $p->user->name.' · '.$p->user->email.' · '.$p->user->phone], ['Producer ID', $p->producer_number], ['NPN', $p->npn],
                ['Business', trim("{$p->business_name} · {$p->business_address}", ' ·')], ['Status', $this->format($p->status, 'pill')],
                ['Commission rate', ($p->commission_rate * 100).'%'], ['Payout', $p->payout_label], ['Auto allowance', $this->format($p->auto_allowance_status, 'pill')],
            ]],
            ['title' => '2,000-token bonus', 'rows' => [
                ['Status', $this->format($p->bonus_status, 'pill')], ['Credited', $p->bonus_credited_on?->format('M j, Y')],
                ['Qualification', $p->monthsQualified().' of 12 months · eligible '.$p->eligibleOn()?->format('M j, Y')],
                ['Conditions', collect($e)->map(fn ($ok, $k) => ucfirst($k).': '.($ok ? 'met' : 'not met'))->implode(' · ')],
                ['Redemption requested', $p->bonus_redemption_requested_at?->format('M j, Y')],
            ]],
            $this->table('Licenses', ['Type', 'Number', 'State', 'Expires', 'Status'], $p->licenses->map(fn ($l) => [$l->license_type, $l->license_number, $l->state, $l->expires_on->format('M j, Y'), $l->statusLabel()])),
            $this->table('Documents', ['Name', 'Status', 'Uploaded'], $p->documents->map(fn ($d) => [$d->name, $this->format($d->status, 'pill'), $d->uploaded_at?->format('M j, Y')])),
            $this->table('Recent commissions', ['Policy', 'Insured', 'Written', 'Premium', 'Amount', 'Status'], $p->commissions()->latest('written_on')->limit(10)->get()
                ->map(fn ($c) => [$c->policy_reference, $c->insured_name, $c->written_on->format('M j, Y'), '$'.number_format($c->premium, 2), '$'.number_format($c->amount, 2), $this->format($c->status, 'pill')])),
        ];
    }

    public function actions(Model $p): array
    {
        $pendingLicenses = $p->licenses->whereNull('verified_at')->mapWithKeys(fn ($l) => [$l->id => "{$l->license_type} · {$l->state} · {$l->license_number}"])->all();
        $reviewDocs = $p->documents->where('status', 'pending_review')->mapWithKeys(fn ($d) => [$d->id => $d->name])->all();

        return array_values(array_filter([
            $pendingLicenses ? ['key' => 'verify_license', 'label' => 'Verify license', 'style' => 'copper', 'fields' => [['name' => 'license_id', 'label' => 'License', 'type' => 'select', 'options' => $pendingLicenses, 'rules' => ['required', 'integer']]]] : null,
            $reviewDocs ? ['key' => 'review_document', 'label' => 'Review document', 'style' => 'line', 'fields' => [
                ['name' => 'document_id', 'label' => 'Document', 'type' => 'select', 'options' => $reviewDocs, 'rules' => ['required', 'integer']],
                ['name' => 'decision', 'label' => 'Decision', 'type' => 'select', 'options' => ['verified' => 'Verify', 'rejected' => 'Reject'], 'rules' => ['required', 'in:verified,rejected']],
            ]] : null,
            ['key' => 'request_document', 'label' => 'Request a document', 'style' => 'line', 'fields' => [['name' => 'name', 'label' => 'Document name', 'rules' => ['required', 'string', 'max:120']]]],
            ['key' => 'commission', 'label' => 'Record commission', 'style' => 'line', 'fields' => [
                ['name' => 'reference', 'label' => 'Policy reference', 'rules' => ['required', 'string', 'max:60']],
                ['name' => 'insured', 'label' => 'Insured', 'rules' => ['required', 'string', 'max:160']],
                ['name' => 'line', 'label' => 'Line of business', 'rules' => ['required', 'string', 'max:60']],
                ['name' => 'written_on', 'label' => 'Written on', 'type' => 'date', 'rules' => ['required', 'date']],
                ['name' => 'premium', 'label' => 'Premium ($)', 'type' => 'number', 'rules' => ['required', 'numeric', 'min:0']],
                ['name' => 'commission_base', 'label' => 'Commission base ($)', 'type' => 'number', 'rules' => ['required', 'numeric', 'min:0']],
            ]],
            $p->bonus_status === 'redemption_requested' ? ['key' => 'bonus_redeemed', 'label' => 'Mark bonus redeemed', 'style' => 'copper', 'confirm' => 'Confirms the 2,000-token bonus has been paid out.'] : null,
        ]));
    }

    public function runAction(Model $p, string $action, array $data): string
    {
        $tell = fn (string $cat, string $title, string $text, string $section) => $p->user->notify(new MemberAlert($cat, $title, $text, $section, mail: true, portal: 'producer'));

        return match ($action) {
            'verify_license' => tap('License verified.', function () use ($p, $data, $tell) {
                $p->licenses()->findOrFail($data['license_id'])->update(['verified_at' => now()]);
                app(ProducerService::class)->syncBonus($p->refresh());
                $tell('Licensing', 'License verified', 'Your license has been verified by COSPARK.', 'licensing');
            }),
            'review_document' => tap('Document reviewed.', function () use ($p, $data, $tell) {
                $doc = $p->documents()->findOrFail($data['document_id']);
                $doc->update(['status' => $data['decision'] === 'verified' ? 'verified' : 'needed', 'path' => $data['decision'] === 'verified' ? $doc->path : null]);
                $tell('Documents', $data['decision'] === 'verified' ? "{$doc->name} verified" : "{$doc->name} needs to be uploaded again", 'See Documents in your portal.', 'documents');
            }),
            'request_document' => tap('Document requested.', function () use ($p, $data, $tell) {
                $p->documents()->create(['name' => $data['name'], 'status' => 'needed']);
                $tell('Documents', "Upload your {$data['name']}", 'Requested by COSPARK.', 'documents');
            }),
            'commission' => tap('Commission recorded.', function () use ($p, $data, $tell) {
                $c = app(ProducerService::class)->recordCommission($p, $data);
                $tell('Commission', 'Commission recorded · $'.number_format($c->amount, 2), "{$c->policy_reference} · {$c->insured_name}", 'commission');
            }),
            'bonus_redeemed' => tap('Bonus marked as redeemed.', function () use ($p, $tell) {
                $p->update(['bonus_status' => 'redeemed']);
                $tell('Tokens', 'Producer bonus redeemed', '2,000 tokens have been paid out.', 'tokens');
            }),
        };
    }
}
