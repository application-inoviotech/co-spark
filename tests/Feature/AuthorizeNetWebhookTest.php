<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\PaymentWebhookEvent;
use App\Models\User;
use App\Services\Billing\AuthorizeNetWebhookProcessor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthorizeNetWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $key;

    public function createApplication(): \Illuminate\Foundation\Application
    {
        $app = parent::createApplication();
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('session.driver', 'array');

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->key = str_repeat('A1', 64);
        config(['services.authorizenet.signature_key' => $this->key, 'services.authorizenet.sandbox' => true]);
        Http::preventStrayRequests();
        Notification::fake();
    }

    private function body(string $type = 'authcapture.created', string $transaction = '123'): string
    {
        return json_encode(['notificationId' => (string) Str::uuid(), 'eventType' => 'net.authorize.payment.'.$type,
            'payload' => ['entityName' => 'transaction', 'id' => $transaction]], JSON_UNESCAPED_SLASHES);
    }

    private function send(string $body, ?string $signature = null): \Illuminate\Testing\TestResponse
    {
        return $this->call('POST', '/webhooks/authorizenet', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_ANET_SIGNATURE' => $signature ?? 'sha512='.strtoupper(hash_hmac('sha512', $body, hex2bin($this->key))),
        ], $body);
    }

    private function payment(string $status = 'succeeded'): Payment
    {
        $user = User::create(['first_name' => 'QA', 'last_name' => 'Webhook', 'email' => Str::uuid().'@example.invalid', 'password' => 'Test-only!Password26', 'role' => 'member', 'status' => 'active']);

        return $user->payments()->create(['type' => Payment::TYPE_DUES, 'description' => 'QA', 'amount' => 25, 'status' => $status, 'gateway' => 'authorizenet', 'gateway_reference' => '123']);
    }

    private function gateway(array $transaction): void
    {
        Http::swap(new \Illuminate\Http\Client\Factory);
        Http::preventStrayRequests();
        Http::fake(['https://apitest.authorize.net/xml/v1/request.api' => Http::response(['messages' => ['resultCode' => 'Ok'], 'transaction' => $transaction])]);
    }

    private function process(): PaymentWebhookEvent
    {
        $event = PaymentWebhookEvent::firstOrFail();
        app(AuthorizeNetWebhookProcessor::class)->process($event->id);

        return $event->fresh();
    }

    public function test_valid_signed_notification_is_durable_before_ack_and_replays_do_not_duplicate(): void
    {
        $body = $this->body();
        $this->send($body)->assertOk()->assertJson(['received' => true]);
        $this->send($body)->assertOk();
        $this->assertDatabaseCount('payment_webhook_events', 1);
        $this->assertDatabaseHas('payment_webhook_events', ['status' => 'pending', 'attempts' => 0]);
        Http::assertNothingSent();
        $this->assertDatabaseCount('payments', 0);
    }

    public function test_text_key_representation_is_supported_and_modified_raw_body_rejected(): void
    {
        $body = $this->body();
        $signature = 'sha512='.hash_hmac('sha512', $body, $this->key);
        $this->send($body, $signature)->assertOk();
        $this->send($body.' ', $signature)->assertUnauthorized();
        $this->send($body, 'sha512='.str_repeat('0', 128))->assertUnauthorized();
        $this->send($body, '')->assertUnauthorized();
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_missing_key_fails_closed_and_malformed_payloads_are_rejected(): void
    {
        config(['services.authorizenet.signature_key' => null]);
        $this->send($this->body())->assertStatus(503);
        config(['services.authorizenet.signature_key' => $this->key]);
        $this->send('{bad json')->assertStatus(422);
        $body = json_decode($this->body(), true);
        $body['payload']['id'] = ['123'];
        $this->send(json_encode($body))->assertStatus(422);
        $this->send(str_repeat('a', 65537))->assertStatus(413);
        $this->assertDatabaseCount('payment_webhook_events', 0);
    }

    public function test_same_notification_identifier_with_changed_content_is_a_conflict(): void
    {
        $body = $this->body();
        $this->send($body)->assertOk();
        $this->send(str_replace('"123"', '"456"', $body))->assertStatus(409);
        $this->assertDatabaseCount('payment_webhook_events', 1);
    }

    public function test_only_webhook_is_csrf_exempt(): void
    {
        $this->app->instance('env', 'production');
        $this->send($this->body())->assertOk();
        $this->post('/register', [])->assertStatus(419);
    }

    public function test_capture_is_verified_without_creating_another_payment_or_tokens(): void
    {
        $p = $this->payment();
        $this->gateway(['transId' => '123', 'transactionStatus' => 'capturedPendingSettlement', 'transactionType' => 'authCaptureTransaction', 'authAmount' => 25]);
        $this->send($this->body())->assertOk();
        $event = $this->process();
        $this->assertSame('processed', $event->status);
        app(AuthorizeNetWebhookProcessor::class)->process($event->id);
        Http::assertSentCount(1);
        $this->assertSame('succeeded', $p->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('token_transactions', 0);
    }

    public function test_void_is_reconciled_and_duplicate_event_ids_do_not_issue_money_or_tokens(): void
    {
        $p = $this->payment();
        app(\App\Services\TokenLedger::class)->credit($p->user, 'DT', 12, 'earned', 'QA dues', $p);
        $this->gateway(['transId' => '123', 'transactionStatus' => 'voided', 'transactionType' => 'authCaptureTransaction', 'authAmount' => 25]);
        $this->send($this->body('void.created'))->assertOk();
        $this->assertSame('processed', $this->process()->status);
        $this->assertSame('refunded', $p->fresh()->status);
        // A different notification for the same transaction cannot credit/reverse a ledger twice.
        $this->send($this->body('authcapture.created'))->assertOk();
        $this->artisan('billing:webhooks')->assertSuccessful();
        $this->assertSame('refunded', $p->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('token_transactions', 2);
        $this->assertSame(0.0, app(\App\Services\TokenLedger::class)->balance($p->user, 'DT'));
    }

    public function test_refund_matches_original_transaction_and_partial_refund_requires_review(): void
    {
        $p = $this->payment();
        $this->gateway(['transId' => '456', 'refTransId' => '123', 'transactionStatus' => 'refundSettledSuccessfully', 'transactionType' => 'refundTransaction', 'authAmount' => 10, 'settleAmount' => 10]);
        $this->send($this->body('refund.created', '456'))->assertOk();
        $this->assertSame('needs_review', $this->process()->status);
        $this->assertSame('succeeded', $p->fresh()->status);
        $this->gateway(['transId' => '789', 'refTransId' => '123', 'transactionStatus' => 'refundSettledSuccessfully', 'transactionType' => 'refundTransaction', 'authAmount' => 25, 'settleAmount' => 25]);
        $this->send($this->body('refund.created', '789'))->assertOk();
        $this->artisan('billing:webhooks')->assertExitCode(1);
        $this->assertSame('refunded', $p->fresh()->status);
    }

    public function test_late_capture_cannot_resurrect_a_refunded_payment(): void
    {
        $p = $this->payment('refunded');
        $this->gateway(['transId' => '123', 'transactionStatus' => 'settledSuccessfully', 'transactionType' => 'authCaptureTransaction', 'authAmount' => 25]);
        $this->send($this->body())->assertOk();
        $this->assertSame('processed', $this->process()->status);
        $this->assertSame('refunded', $p->fresh()->status);
    }

    public function test_unmatched_transaction_retries_then_surfaces_review_without_fabricating_payment(): void
    {
        $this->gateway(['transId' => '123', 'transactionStatus' => 'settledSuccessfully', 'authAmount' => 25]);
        $this->send($this->body())->assertOk();
        $event = $this->process();
        $this->assertSame('pending', $event->status);
        $this->assertSame(1, $event->attempts);
        $this->assertTrue($event->next_attempt_at->isFuture());
        $event->update(['attempts' => 9, 'next_attempt_at' => null]);
        $this->assertSame('needs_review', $this->process()->status);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('token_transactions', 0);
    }

    public function test_gateway_errors_are_retried_without_acknowledging_processing_as_complete(): void
    {
        $this->payment();
        Http::fake(['*' => Http::response(['messages' => ['resultCode' => 'Error']], 500)]);
        $this->send($this->body())->assertOk();
        $event = $this->process();
        $this->assertSame('pending', $event->status);
        $this->assertNotNull($event->next_attempt_at);
        $this->assertNull($event->processed_at);
        $this->gateway(['transId' => '123', 'transactionStatus' => 'settledSuccessfully', 'authAmount' => 25]);
        $event->update(['next_attempt_at' => null]);
        $recovered = $this->process();
        $this->assertSame('processed', $recovered->status);
        $this->assertSame(2, $recovered->attempts);
    }

    public function test_mismatched_id_amount_and_unfulfilled_charge_do_not_change_payment(): void
    {
        $p = $this->payment('pending');
        foreach ([['transId' => '999', 'authAmount' => 25], ['transId' => '123', 'authAmount' => 1], ['transId' => '123', 'authAmount' => 25]] as $transaction) {
            $this->gateway($transaction + ['transactionStatus' => 'settledSuccessfully', 'transactionType' => 'authCaptureTransaction']);
            $this->send($this->body())->assertOk();
            $this->artisan('billing:webhooks')->assertExitCode(1);
        }
        $this->assertSame('pending', $p->fresh()->status);
        $this->assertDatabaseCount('token_transactions', 0);
    }

    public function test_unsupported_events_and_other_environment_do_not_call_gateway(): void
    {
        $this->send($this->body('authorization.created'))->assertOk();
        $this->assertSame('ignored', $this->process()->status);
        $this->send($this->body())->assertOk();
        config(['services.authorizenet.sandbox' => false]);
        $this->artisan('billing:webhooks')->assertSuccessful();
        Http::assertNothingSent();
        $this->assertDatabaseHas('payment_webhook_events', ['status' => 'pending', 'environment' => 'sandbox']);
    }

    public function test_historical_unknown_or_other_environment_payments_cannot_be_mutated(): void
    {
        $p = $this->payment();
        $this->assertSame('sandbox', $p->gateway_environment);
        $p->update(['gateway_environment' => null]);
        $this->gateway(['transId' => '123', 'transactionStatus' => 'voided', 'transactionType' => 'authCaptureTransaction', 'authAmount' => 25]);
        $this->send($this->body('void.created'))->assertOk();
        $this->assertSame('needs_review', $this->process()->status);
        $this->assertSame('succeeded', $p->fresh()->status);
        $p->update(['gateway_environment' => 'production']);
        $this->send($this->body('void.created'))->assertOk();
        $this->artisan('billing:webhooks')->assertExitCode(1);
        $this->assertSame('succeeded', $p->fresh()->status);
    }

    public function test_review_can_be_explicitly_requeued_after_resolving_the_cause(): void
    {
        $p = $this->payment();
        $this->gateway(['transId' => '123', 'transactionStatus' => 'settledSuccessfully', 'authAmount' => 1]);
        $this->send($this->body())->assertOk();
        $review = $this->process();
        $this->assertSame('needs_review', $review->status);
        $this->gateway(['transId' => '123', 'transactionStatus' => 'settledSuccessfully', 'authAmount' => 25]);
        $this->artisan('billing:webhooks', ['--retry' => $review->notification_id])->assertSuccessful();
        $this->assertSame('processed', $review->fresh()->status);
        $this->assertSame('succeeded', $p->fresh()->status);
        $this->assertDatabaseCount('payments', 1);
        $this->assertDatabaseCount('token_transactions', 0);
    }
}
