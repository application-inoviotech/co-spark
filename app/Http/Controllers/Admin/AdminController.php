<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Attachment;
use App\Models\Commission;
use App\Models\Lead;
use App\Models\Membership;
use App\Models\Partner;
use App\Models\Payment;
use App\Models\Policy;
use App\Models\Producer;
use App\Models\ProducerApplication;
use App\Models\QrRedemption;
use App\Models\Setting;
use App\Models\TokenTransaction;
use App\Models\TokenTransfer;
use App\Models\User;
use App\Models\VacationRequest;
use App\Services\BenefitsService;
use App\Support\SystemStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Admin dashboard, partner QR scanning, reports, settings and private file downloads. */
class AdminController extends Controller
{
    /** SOW: configurable summary cards and key operational information. */
    public function dashboard(): Response
    {
        $membersOnPlan = fn ($plan) => User::where('role', 'member')->where('status', 'active')
            ->whereHas('membership', fn ($q) => $q->where('status', 'active')->where('plan', $plan))->count();
        $kpis = [
            ['Total members', User::where('role', 'member')->count(), null],
            ['Active members', User::where('role', 'member')->where('status', 'active')->count(), null],
            ['Free · Associate · Premium', $membersOnPlan('free').' · '.$membersOnPlan('associate').' · '.$membersOnPlan('premium'), null],
            ['Active policies', Policy::where('status', 'active')->count(), 'policy'],
            ['Policies expiring (30 days)', Policy::where('status', 'active')->whereBetween('expires_on', [today(), today()->addDays(30)])->count(), 'policy?renewal=30'],
            ['Policies pending renewal (90 days)', Policy::where('status', 'active')->whereBetween('expires_on', [today(), today()->addDays(90)])->count(), 'policy?renewal=90'],
            ['Token balances (DT · IT · VT)', collect(['DT', 'IT', 'VT'])->map(fn ($t) => number_format(TokenTransaction::where('token_type', $t)->sum('amount')))->implode(' · '), 'token-transaction'],
            ['Pending token transfers', TokenTransfer::where('status', 'pending')->count(), 'token-transfer?status=pending'],
            ['Pending token redemptions', Producer::where('bonus_status', 'redemption_requested')->count(), 'producer'],
            ['Dues collected (this month)', '$'.number_format(Payment::where('type', Payment::TYPE_DUES)->where('status', 'succeeded')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'), 2), 'payment'],
            ['Failed payments', Payment::where('status', 'failed')->where('created_at', '>=', now()->subDays(30))->count(), 'payment?status=failed'],
            ['Refunds (30 days)', Payment::where('status', 'refunded')->where('updated_at', '>=', now()->subDays(30))->count(), 'payment?status=refunded'],
            ['Pending cancellations', Membership::where('status', 'active')->whereNotNull('cancelled_at')->count(), 'membership?status=active'],
            ['Producer applications', ProducerApplication::whereNotIn('status', ['approved', 'rejected'])->count(), 'producer-application'],
            ['Active producers', Producer::where('status', 'active')->count(), 'producer'],
            ['Pending appointments', Appointment::where('status', 'pending')->count(), 'appointment?status=pending'],
            ['New leads / quotes', Lead::where('status', 'new')->count(), 'lead?status=new'],
            ['Vacation requests', VacationRequest::where('status', 'pending')->count(), 'vacation-request?status=pending'],
            ['QR redemptions (30 days)', QrRedemption::where('created_at', '>=', now()->subDays(30))->count(), 'qr-code'],
            ['Partner programs', Partner::where('is_active', true)->count(), 'partner'],
        ];

        return Inertia::render('Dashboard', [
            'system' => SystemStatus::summary(),
            'kpis' => collect($kpis)->map(fn ($k) => ['label' => $k[0], 'value' => $k[1], 'href' => $k[2] ? url('admin/'.$k[2]) : null]),
            'recentLeads' => Lead::latest()->limit(6)->get()->map(fn ($l) => [$l->reference, $l->full_name, $l->interestLabel(), $l->created_at->diffForHumans(), route('admin.resources.show', ['lead', $l->id])]),
            'upcoming' => Appointment::whereIn('status', ['pending', 'confirmed'])->whereDate('preferred_date', '>=', today())->orderBy('preferred_date')->limit(6)->get()->map(fn ($a) => [$a->preferred_date->format('M j').' · '.$a->preferred_time, $a->name, $a->type_label, ucfirst($a->status), route('admin.resources.show', ['appointment', $a->id])]),
        ]);
    }

    /** Partner terminal (SOW: scan QR → validate → redeem). */
    public function qrScan(): Response
    {
        return Inertia::render('QrScan', ['partners' => Partner::active()->where('is_vacation', false)->pluck('name', 'id')]);
    }

    public function qrRedeem(Request $request, BenefitsService $benefits): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
            'partner_id' => ['required', 'exists:partners,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:10000'],
            'location' => ['nullable', 'string', 'max:120'],
        ]);
        $result = $benefits->redeemQr($data['code'], Partner::findOrFail($data['partner_id']), $data['amount'], $data['location'] ?? null);

        return back()->with('success', "Redeemed {$data['amount']} DT for {$result['member']->name} · {$result['redemption']->reference}");
    }

    public function reports(): Response
    {
        return Inertia::render('Reports', ['reports' => collect(self::REPORTS)->map(fn ($r, $key) => ['label' => $r[0], 'href' => route('admin.reports.export', $key)])->values()]);
    }

    /** CSV exports (SOW: Reports & Analytics). */
    private const REPORTS = [
        'members' => ['Members & memberships'], 'payments' => ['Payments & refunds'], 'tokens' => ['Token ledger'],
        'policies' => ['Policies & premiums'], 'leads' => ['Leads & quotes'], 'commissions' => ['Producer commissions'],
        'appointments' => ['Appointments'], 'vacations' => ['Vacation requests'], 'redemptions' => ['QR redemptions'],
    ];

    public function export(string $report): StreamedResponse
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        [$headings, $rows] = match ($report) {
            'members' => [['Member ID', 'Name', 'Email', 'Role', 'Plan', 'Membership status', 'Paid through', 'Registered'],
                User::with('membership')->cursor()->map(fn ($u) => [$u->member_number, $u->name, $u->email, $u->role, $u->membership?->plan, $u->membership?->status, $u->membership?->current_period_end?->toDateString(), $u->created_at->toDateString()])],
            'payments' => [['Reference', 'Member', 'Type', 'Description', 'Amount', 'Status', 'Date'],
                Payment::with('user')->cursor()->map(fn ($p) => [$p->reference, $p->user->email, $p->type, $p->description, $p->amount, $p->status, $p->created_at->toDateString()])],
            'tokens' => [['Date', 'Member', 'Token', 'Type', 'Amount', 'Description', 'Status'],
                TokenTransaction::with('user')->cursor()->map(fn ($t) => [$t->created_at->toDateString(), $t->user->email, $t->token_type, $t->kind, $t->amount, $t->description, $t->status])],
            'policies' => [['Policy', 'Type', 'Member', 'Carrier', 'Annual premium', 'Expires', 'Status'],
                Policy::with('user')->cursor()->map(fn ($p) => [$p->policy_number, $p->type, $p->user->email, $p->carrier, $p->annual_premium, $p->expires_on->toDateString(), $p->status])],
            'leads' => [['Reference', 'Name', 'Email', 'Phone', 'Interest', 'Source', 'Status', 'Received'],
                Lead::cursor()->map(fn ($l) => [$l->reference, $l->full_name, $l->email, $l->phone, $l->insurance_type, $l->source, $l->status, $l->created_at->toDateString()])],
            'commissions' => [['Producer', 'Policy', 'Insured', 'Written', 'Premium', 'Commission', 'Status', 'Paid on'],
                Commission::with('producer.user')->cursor()->map(fn ($c) => [$c->producer->user->name, $c->policy_reference, $c->insured_name, $c->written_on->toDateString(), $c->premium, $c->amount, $c->status, $c->paid_on?->toDateString()])],
            'appointments' => [['Reference', 'Name', 'Email', 'Type', 'Date', 'Time', 'Status'],
                Appointment::cursor()->map(fn ($a) => [$a->reference, $a->name, $a->email, $a->type, $a->preferred_date->toDateString(), $a->preferred_time, $a->status])],
            'vacations' => [['Reference', 'Member', 'Property', 'Check-in', 'Check-out', 'DT', 'Status'],
                VacationRequest::with('user', 'partner')->cursor()->map(fn ($v) => [$v->reference, $v->user->email, $v->partner->name, $v->check_in->toDateString(), $v->check_out->toDateString(), $v->tokens_applied, $v->status])],
            'redemptions' => [['Reference', 'Member', 'Partner', 'Amount (DT)', 'Date'],
                QrRedemption::with('partner')->cursor()->map(fn ($r) => [$r->reference, $r->user_id, $r->partner?->name, $r->amount, $r->created_at->toDateString()])],
        };

        return response()->streamDownload(function () use ($headings, $rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, \App\Support\CsvRow::safe($row));
            }
            fclose($out);
        }, "cospark-{$report}-".today()->toDateString().'.csv', ['Content-Type' => 'text/csv']);
    }

    public function settings(): Response
    {
        return Inertia::render('Settings', [
            'fields' => collect(Setting::EDITABLE)->map(fn ($label, $key) => [
                'name' => str_replace('.', '__', $key), 'label' => $label, 'type' => Setting::type($key),
                'hint' => $key === 'appointment_times' ? 'One per line. Keep "After hours — request" last; it is the after-hours option.' : null,
            ])->values(),
            'values' => collect(Setting::EDITABLE)->mapWithKeys(fn ($l, $key) => [str_replace('.', '__', $key) => match (Setting::type($key)) {
                'lines' => implode("\n", config("cospark.{$key}")),
                'checkbox' => (bool) config("cospark.{$key}"),
                default => config("cospark.{$key}"),
            }]),
        ]);
    }

    /** Send queued emails/texts now — for when the server cron is not running the worker. */
    public function processQueue(): RedirectResponse
    {
        $before = SystemStatus::summary()['pending'];
        Artisan::call('queue:work', ['connection' => 'database', '--stop-when-empty' => true, '--tries' => 3, '--max-time' => 25]);
        $left = SystemStatus::summary()['pending'];

        return back()->with('success', ($before - $left).' queued message(s) processed'.($left ? ", {$left} still waiting — run again." : '.'));
    }

    /** Put every failed job back on the queue (e.g. after fixing the mail settings). */
    public function retryFailed(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return back()->with('success', 'Failed messages were queued again. Use "Send queued now" or wait for the next cron run.');
    }

    public function saveSettings(Request $request): RedirectResponse
    {
        $rules = collect(Setting::EDITABLE)->mapWithKeys(fn ($l, $key) => [str_replace('.', '__', $key) => match (Setting::type($key)) {
            'textarea' => ['nullable', 'string', 'max:20000'],
            'lines' => ['required', 'string', 'max:2000'],
            'checkbox' => ['boolean'],
            'number' => ['required', 'integer', 'min:0', 'max:30'],
            default => ['nullable', 'string', 'max:255'],
        }])->all();
        $data = $request->validate($rules);
        foreach (Setting::EDITABLE as $key => $label) {
            $value = $data[str_replace('.', '__', $key)] ?? null;
            $value = match (Setting::type($key)) {
                'lines' => json_encode(array_values(array_filter(array_map('trim', preg_split('/\R/', (string) $value))))),
                'checkbox' => $value ? '1' : '0',
                default => $value,
            };
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Setting::applyToConfig(); // refresh the cached copy once, after every key is stored

        return back()->with('success', 'Settings saved.');
    }

    /** Private uploads (claims, quotes, applications) for staff. */
    public function file(Attachment $attachment): StreamedResponse
    {
        return Storage::download($attachment->path, $attachment->original_name);
    }
}
