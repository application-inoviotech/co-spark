<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Billing moves from Stripe to Authorize.net: saved cards live in Authorize.net CIM profiles and
 * renewals are charged by the app's daily billing job, so Stripe ids and price ids are dropped.
 *
 * MySQL does not roll back schema changes, so every step checks whether it already ran: a run
 * that stopped halfway can simply be run again. CHANGE (not RENAME COLUMN) keeps it working on
 * MySQL 5.7 and MariaDB as well as MySQL 8.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'stripe_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropUnique(['stripe_customer_id']);
                $table->dropColumn(['stripe_customer_id', 'stripe_sub_accounts_subscription_id']);
            });
        }
        if (! Schema::hasColumn('users', 'gateway_customer_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('gateway_customer_id')->nullable()->unique()->after('remember_token'); // Authorize.net customer profile id
                $table->timestamp('sub_accounts_paid_through')->nullable()->after('gateway_customer_id'); // Premium sub-account fees
            });
        }

        if (Schema::hasColumn('memberships', 'stripe_subscription_id')) {
            Schema::table('memberships', function (Blueprint $table) {
                $table->dropUnique(['stripe_subscription_id']);
                $table->dropColumn('stripe_subscription_id');
            });
        }
        if (Schema::hasColumn('memberships', 'stripe_status')) {
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('memberships', fn (Blueprint $table) => $table->renameColumn('stripe_status', 'billing_status'));
            } else {
                DB::statement('ALTER TABLE memberships CHANGE stripe_status billing_status VARCHAR(30) NULL');
            } // active | past_due | canceled
        }

        if (Schema::hasColumn('membership_plans', 'stripe_price_monthly')) {
            Schema::table('membership_plans', function (Blueprint $table) {
                $table->dropColumn(['stripe_price_monthly', 'stripe_price_quarterly', 'stripe_price_biannual', 'stripe_price_annual']);
            });
        }

        // Stripe card tokens cannot be charged on Authorize.net; members add their card again
        DB::table('payment_methods')->where('gateway_token', 'like', 'pm_%')->delete();
    }

    public function down(): void
    {
        Schema::table('membership_plans', function (Blueprint $table) {
            foreach (['monthly', 'quarterly', 'biannual', 'annual'] as $frequency) {
                $table->string("stripe_price_{$frequency}")->nullable();
            }
        });
        if (DB::getDriverName() === 'sqlite') {
            Schema::table('memberships', fn (Blueprint $table) => $table->renameColumn('billing_status', 'stripe_status'));
        } else {
            DB::statement('ALTER TABLE memberships CHANGE billing_status stripe_status VARCHAR(30) NULL');
        }
        Schema::table('memberships', function (Blueprint $table) {
            $table->string('stripe_subscription_id')->nullable()->unique()->after('cancelled_at');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['gateway_customer_id']);
            $table->dropColumn(['gateway_customer_id', 'sub_accounts_paid_through']);
            $table->string('stripe_customer_id')->nullable()->unique()->after('remember_token');
            $table->string('stripe_sub_accounts_subscription_id')->nullable()->after('stripe_customer_id');
        });
    }
};
