<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Stripe customer / subscription references for recurring membership billing. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('stripe_customer_id')->nullable()->unique()->after('remember_token');
            $table->string('stripe_sub_accounts_subscription_id')->nullable()->after('stripe_customer_id'); // Premium sub-account fees
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->string('stripe_subscription_id')->nullable()->unique()->after('cancelled_at');
            $table->string('stripe_status', 30)->nullable()->after('stripe_subscription_id'); // active | past_due | canceled …
            $table->timestamp('expired_at')->nullable()->after('stripe_status');
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index('gateway_reference'); // invoice / payment intent id, used to keep webhooks idempotent
        });
    }

    public function down(): void
    {
        Schema::table('payments', fn (Blueprint $table) => $table->dropIndex(['gateway_reference']));
        Schema::table('memberships', fn (Blueprint $table) => $table->dropColumn(['stripe_subscription_id', 'stripe_status', 'expired_at']));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['stripe_customer_id', 'stripe_sub_accounts_subscription_id']));
    }
};
