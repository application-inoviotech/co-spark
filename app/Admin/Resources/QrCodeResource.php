<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\MemberQrCode;
use App\Notifications\QrCodeLink;
use App\Services\BenefitsService;
use App\Support\QrSvg;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** SOW: Admin QR Code Management — status, regenerate, send, redemption history. Codes are issued from a user. */
class QrCodeResource extends Resource
{
    public static string $model = MemberQrCode::class;

    public static string $label = 'QR codes';

    public static string $singular = 'QR code';

    public static string $group = 'Benefits';

    public static array $search = ['code', 'user.email', 'user.last_name'];

    public function query(): Builder
    {
        return MemberQrCode::with('user.membership')->latest('id');
    }

    public function title(Model $qr): string
    {
        return $qr->user->name.' · QR';
    }

    public function columns(): array
    {
        return [
            ['key' => 'user.name', 'label' => 'Member'],
            ['key' => 'user.membership.plan', 'label' => 'Plan', 'type' => 'pill'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'created_at', 'label' => 'Generated', 'type' => 'date'],
        ];
    }

    public function filters(): array
    {
        return ['status' => ['label' => 'Status', 'options' => self::options(['active', 'inactive'])]];
    }

    public function detail(Model $qr): array
    {
        return [
            ['title' => 'QR code', 'rows' => [['Member', $qr->user->name.' · '.$qr->user->member_number], ['Code', $qr->code], ['Status', $this->format($qr->status, 'pill')], ['Generated', $qr->created_at->format('M j, Y')]], 'svg' => QrSvg::make($qr->code)],
            $this->table('Redemptions', ['Date', 'Reference', 'Partner', 'Amount'], $qr->user->qrRedemptions()->with('partner')->latest()->get()
                ->map(fn ($r) => [$r->created_at->format('M j, Y'), $r->reference, trim($r->partner?->name.' '.$r->location), "{$r->amount} DT"])),
        ];
    }

    public function actions(Model $qr): array
    {
        return [
            $qr->status === 'active' ? ['key' => 'inactive', 'label' => 'Deactivate', 'style' => 'danger'] : ['key' => 'active', 'label' => 'Activate', 'style' => 'copper'],
            ['key' => 'reissue', 'label' => 'Regenerate', 'style' => 'line', 'confirm' => 'The current code stops working.'],
            ['key' => 'send', 'label' => 'Send to member', 'style' => 'line'],
        ];
    }

    public function runAction(Model $qr, string $action, array $data): string
    {
        return match ($action) {
            'active', 'inactive' => tap('QR code '.($action === 'active' ? 'activated.' : 'deactivated.'), fn () => $qr->update(['status' => $action])),
            'reissue' => tap('A new QR code was issued.', fn () => app(BenefitsService::class)->issueQr($qr->user)),
            'send' => tap($qr->user->sms_opt_in ? 'Sent by email and text.' : 'Sent by email (the member has not opted in to texts).', fn () => $qr->user->notify(new QrCodeLink)),
        };
    }
}
