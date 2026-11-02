<?php

namespace App\Console\Commands;

use App\Models\PaymentWebhookEvent;
use App\Services\Billing\AuthorizeNetWebhookProcessor;
use Illuminate\Console\Command;

class ProcessPaymentWebhooks extends Command
{
    protected $signature = 'billing:webhooks {--limit=50 : Maximum pending events per run} {--retry= : Notification UUID to requeue after reviewing the cause}';

    protected $description = 'Reconcile the durable Authorize.net webhook inbox and show outstanding review items';

    public function handle(AuthorizeNetWebhookProcessor $processor): int
    {
        $environment = config('services.authorizenet.sandbox') ? 'sandbox' : 'production';
        if ($notification = $this->option('retry')) {
            PaymentWebhookEvent::where('environment', $environment)->where('notification_id', $notification)
                ->where('status', 'needs_review')->update(['status' => 'pending', 'attempts' => 0, 'next_attempt_at' => null, 'processed_at' => null]);
        }
        $ids = PaymentWebhookEvent::where('environment', $environment)->where('status', 'pending')
            ->where(fn ($q) => $q->whereNull('next_attempt_at')->orWhere('next_attempt_at', '<=', now()))
            ->orderBy('id')->limit(max(1, min(500, (int) $this->option('limit'))))->pluck('id');
        foreach ($ids as $id) {
            $processor->process($id);
        }
        $this->info('Events inspected: '.$ids->count());
        $reviews = PaymentWebhookEvent::where('environment', $environment)->where('status', 'needs_review')->orderBy('id')->limit(50)->get();
        if ($reviews->isNotEmpty()) {
            $this->table(['Notification', 'Reason'], $reviews->map(fn ($e) => [$e->notification_id, $e->last_error])->all());

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
