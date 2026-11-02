<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Member dashboard data (SOW: My Policies, Claims, Payments, Token Wallet, Vacations,
 * QR Code, Notifications, Sub-accounts, Payment Methods).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('relationship', 40)->nullable()->after('parent_id'); // sub-accounts
            $table->json('permissions')->nullable()->after('relationship');      // sub-accounts
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->string('pending_plan', 20)->nullable()->after('plan'); // plan switch at period end
        });

        Schema::table('partners', function (Blueprint $table) {
            $table->decimal('nightly_rate', 8, 2)->nullable()->after('amenities');
            $table->unsignedSmallInteger('tokens_per_night')->nullable()->after('nightly_rate');
        });

        Schema::create('policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40); // Home, Auto, Health, Commercial, Renters, Condo, Life, Umbrella…
            $table->string('policy_number', 60)->unique();
            $table->string('carrier', 120);
            $table->string('insured_name', 160);
            $table->string('covers')->nullable(); // insured address / vehicles / plan description
            $table->date('starts_on');
            $table->date('expires_on');
            $table->decimal('annual_premium', 10, 2);
            $table->decimal('amount_due', 10, 2)->default(0);
            $table->date('due_on')->nullable();
            $table->string('status', 20)->default('active')->index(); // active | pending | cancelled | expired
            $table->string('renewal_note', 120)->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });

        Schema::create('policy_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('policy_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->timestamps();
        });

        Schema::table('claims', function (Blueprint $table) {
            $table->foreignId('policy_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('progress_step')->default(0)->after('status'); // 0 Submitted … 4 Closed
            $table->string('required_action')->nullable()->after('progress_step');
            $table->json('outstanding_documents')->nullable()->after('required_action');
        });

        Schema::create('claim_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('claim_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('author_name', 120); // "You" for the member, "Adjuster · Name" for staff
            $table->boolean('from_member')->default(true);
            $table->text('body');
            $table->timestamps();
        });

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10); // card | bank
            $table->string('brand', 30)->nullable();
            $table->string('bank_name', 80)->nullable();
            $table->string('last4', 4);
            $table->unsignedTinyInteger('exp_month')->nullable();
            $table->unsignedSmallInteger('exp_year')->nullable();
            $table->string('gateway_token')->nullable(); // provider token; never raw card data
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('description')->nullable()->after('type');
            $table->foreignId('payment_method_id')->nullable()->after('method_label')->constrained()->nullOnDelete();
        });

        Schema::table('token_transactions', function (Blueprint $table) {
            $table->string('reference', 40)->nullable()->after('description'); // related payment / policy / request
            $table->string('status', 20)->default('posted')->after('reference'); // posted | pending | forfeited
            $table->foreign('policy_id')->references('id')->on('policies')->nullOnDelete();
        });

        Schema::create('vacation_token_lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lot_number', 20);
            $table->date('purchased_on');
            $table->decimal('face_value', 10, 2);
            $table->decimal('used_value', 10, 2)->default(0);
            $table->string('status', 20)->default('active'); // active | refunded
            $table->timestamps();
        });

        Schema::create('vacation_requests', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->constrained()->cascadeOnDelete();
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedTinyInteger('guests');
            $table->text('notes')->nullable();
            $table->unsignedInteger('tokens_applied')->default(0);
            $table->decimal('payment_amount', 10, 2)->default(0);
            $table->string('payment_status', 20)->default('not_due'); // not_due | pending | paid
            $table->string('status', 20)->default('pending')->index(); // pending | confirmed | declined | cancelled | completed
            $table->text('instructions')->nullable(); // admin / facility instructions
            $table->timestamps();
        });

        Schema::create('token_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->cascadeOnDelete();
            $table->string('token_type', 2);
            $table->unsignedInteger('quantity');
            $table->string('note')->nullable();
            $table->string('status', 20)->default('pending')->index(); // pending | completed | rejected
            $table->timestamp('indemnity_accepted_at');
            $table->timestamps();
        });

        Schema::create('member_qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->uuid('code')->unique();
            $table->string('status', 20)->default('active'); // active | inactive
            $table->timestamps();
        });

        Schema::create('qr_redemptions', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('partner_id')->nullable()->constrained()->nullOnDelete();
            $table->string('location')->nullable();
            $table->string('token_type', 2)->default('DT');
            $table->unsignedInteger('amount');
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('qr_redemptions');
        Schema::dropIfExists('member_qr_codes');
        Schema::dropIfExists('token_transfers');
        Schema::dropIfExists('vacation_requests');
        Schema::dropIfExists('vacation_token_lots');
        Schema::table('token_transactions', function (Blueprint $table) {
            $table->dropForeign(['policy_id']);
            $table->dropColumn(['reference', 'status']);
        });
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_method_id');
            $table->dropColumn('description');
        });
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('claim_messages');
        Schema::table('claims', function (Blueprint $table) {
            $table->dropConstrainedForeignId('policy_id');
            $table->dropColumn(['progress_step', 'required_action', 'outstanding_documents']);
        });
        Schema::dropIfExists('policy_documents');
        Schema::dropIfExists('policies');
        Schema::table('partners', fn (Blueprint $table) => $table->dropColumn(['nightly_rate', 'tokens_per_night']));
        Schema::table('memberships', fn (Blueprint $table) => $table->dropColumn('pending_plan'));
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn(['relationship', 'permissions']));
    }
};
