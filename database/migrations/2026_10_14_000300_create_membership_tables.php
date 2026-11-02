<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Memberships, payments, the token ledger (DT / IT / VT) and one-time codes
 * used for email verification and password reset.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 20); // free | associate | premium | sub_account
            $table->string('billing_frequency', 20)->default('monthly'); // monthly | quarterly | biannual | annual
            $table->decimal('monthly_fee', 8, 2)->default(0);
            $table->string('status', 20)->default('pending')->index(); // pending | active | expired | cancelled
            $table->boolean('auto_renew')->default(true);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('payable'); // membership, policy, …
            $table->string('type', 30)->index(); // membership_dues | insurance_premium | vacation_tokens | sub_account
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending')->index(); // pending | succeeded | failed | refunded | cancelled
            $table->string('method_label', 60)->nullable(); // e.g. "Visa •••• 4242"
            $table->string('gateway', 30)->nullable();
            $table->string('gateway_reference')->nullable();
            $table->string('failure_reason')->nullable();
            $table->date('period_start')->nullable();
            $table->date('period_end')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::create('token_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('token_type', 2)->index(); // DT | IT | VT
            $table->string('kind', 20); // earned | purchased | accrued | used | transferred_in | transferred_out | forfeited | adjusted
            $table->decimal('amount', 12, 2); // positive = credit, negative = debit
            $table->string('description');
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('policy_id')->nullable()->index();
            $table->string('policy_year', 9)->nullable(); // e.g. "2026-2027" for year-based IT tracking
            $table->timestamps();
        });

        Schema::create('one_time_codes', function (Blueprint $table) {
            $table->id();
            $table->string('email')->index();
            $table->string('purpose', 30); // verify_email | reset_password
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('one_time_codes');
        Schema::dropIfExists('token_transactions');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('memberships');
    }
};
