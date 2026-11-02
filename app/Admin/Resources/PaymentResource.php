<?php

namespace App\Admin\Resources;

use App\Admin\Resource;
use App\Models\Payment;
use App\Notifications\MemberAlert;
use App\Services\Billing\RefundReconciler;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** SOW: Payment & Financial Management — successful, failed, pending and refunded transactions. */
class PaymentResource extends Resource
{
    public static string $model = Payment::class;

    public static string $label = 'Payments';

    public static string $singular = 'Payment';

    public static string $group = 'Billing & tokens';

    public static array $search = ['reference', 'gateway_reference', 'user.email', 'description'];

    public function query(): Builder
    {
        return Payment::with('user')->latest('id');
    }

    public function columns(): array
    {
        return [
            ['key' => 'reference', 'label' => 'Reference'],
            ['key' => 'user.name', 'label' => 'Member'],
            ['key' => 'type', 'label' => 'Type', 'type' => 'pill'],
            ['key' => 'amount', 'label' => 'Amount', 'type' => 'money'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'pill'],
            ['key' => 'created_at', 'label' => 'Date', 'type' => 'date'],
        ];
    }

    public function filters(): array
    {
        return [
            'status' => ['label' => 'Status', 'options' => self::options(['succeeded', 'failed', 'pending', 'refunded'])],
            'type' => ['label' => 'Type', 'options' => self::options([Payment::TYPE_DUES, Payment::TYPE_PREMIUM, Payment::TYPE_VACATION_TOKENS, Payment::TYPE_SUB_ACCOUNT])],
        ];
    }

    public function detail(Model $p): array
    {
        return [['title' => 'Payment', 'rows' => [
            ['Member', $p->user->name.' · '.$p->user->email], ['Description', $p->description], ['Type', $this->format($p->type, 'pill')],
            ['Amount', '$'.number_format($p->amount, 2)], ['Status', $this->format($p->status, 'pill')], ['Failure reason', $p->failure_reason],
            ['Method', $p->method_label], ['Period', $p->period_start ? $p->period_start->format('M j, Y').' – '.$p->period_end?->format('M j, Y') : null],
            ['Paid at', $p->paid_at?->format('M j, Y g:i A')], ['Gateway', $p->gateway], ['Transaction ID', $p->gateway_reference],
            ['Receipt', ['href' => route('dashboard.payments.receipt', $p), 'label' => 'Open receipt']],
        ]]];
    }

    public function actions(Model $p): array
    {
        return $p->status === 'succeeded' && $p->gateway === 'authorizenet' && $p->gateway_reference
            ? [['key' => 'refund', 'label' => 'Refund in full', 'style' => 'danger', 'confirm' => 'Return the full amount through Authorize.net, reverse payment-linked tokens and remove refunded VT lots/paid entitlement. Already-spent tokens may leave a negative balance for review.']]
            : [];
    }

    public function runAction(Model $p, string $action, array $data): string
    {
        $result = app(RefundReconciler::class)->refund($p);
        $p = $result['payment'];
        try {
            $p->user->notify(new MemberAlert('Payments', 'Refund issued · $'.number_format($p->amount, 2), "{$p->description} ({$p->reference}).", 'payments', mail: true));
        } catch (\Throwable $e) {
            report($e);
        }

        return 'Refund issued; linked rewards reconciled.'.($result['review'] ? ' Review: '.$result['review'] : '');
    }
}
