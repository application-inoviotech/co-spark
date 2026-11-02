<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('payments', 'gateway_environment')) {
            Schema::table('payments', function (Blueprint $table) {
                // Leave historic provenance unknown rather than assuming sandbox/production.
                $table->string('gateway_environment', 16)->nullable();
            });
        }
        if (Schema::hasTable('payment_webhook_events')) {
            return;
        }
        Schema::create('payment_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('environment', 16);
            $table->uuid('notification_id');
            $table->string('event_type', 120);
            $table->string('transaction_id', 32)->nullable();
            $table->char('body_hash', 64);
            $table->string('status', 24)->default('pending');
            $table->unsignedInteger('attempts')->default(0);
            $table->string('last_error')->nullable();
            $table->timestamp('next_attempt_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
            $table->unique(['environment', 'notification_id'], 'payment_webhooks_notification_unique');
            $table->index(['environment', 'status', 'next_attempt_at'], 'payment_webhooks_due_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_webhook_events');
        Schema::table('payments', fn (Blueprint $table) => $table->dropColumn('gateway_environment'));
    }
};
