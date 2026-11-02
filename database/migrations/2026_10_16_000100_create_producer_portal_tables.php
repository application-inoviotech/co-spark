<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Producer portal (SOW: Producer Dashboard, Profile, Licensing, Token Tracking,
 * 12-Month Qualification, Commission, Performance, Benefits, Leads, Appointments).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('producers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('producer_application_id')->nullable()->constrained()->nullOnDelete();
            $table->string('producer_number', 20)->unique();
            $table->string('npn', 20)->nullable(); // National Producer Number
            $table->string('business_name')->nullable();
            $table->string('business_address')->nullable();
            $table->string('status', 20)->default('under_review')->index(); // under_review | active | suspended
            $table->date('onboarded_on')->nullable();
            $table->date('qualification_started_on')->nullable(); // start of the 12 months writing with COSPARK
            $table->decimal('commission_rate', 5, 4)->default(0.75);
            $table->string('payout_label', 60)->nullable(); // e.g. "Direct deposit · Chase •5520"
            $table->unsignedInteger('bonus_tokens')->default(2000);
            $table->string('bonus_status', 30)->default('not_credited'); // not_credited | locked | available | redemption_requested | redeemed
            $table->date('bonus_credited_on')->nullable();
            $table->timestamp('bonus_redemption_requested_at')->nullable();
            $table->string('auto_allowance_status', 30)->default('in_progress');
            $table->timestamps();
        });

        Schema::create('producer_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained()->cascadeOnDelete();
            $table->string('license_type', 60);
            $table->string('license_number', 60);
            $table->string('state', 2);
            $table->date('issued_on')->nullable();
            $table->date('expires_on');
            $table->string('document_path')->nullable();
            $table->timestamp('verified_at')->nullable(); // set by admin after verification
            $table->timestamps();
        });

        Schema::create('producer_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('path')->nullable(); // null while requested and not yet uploaded
            $table->string('status', 20)->default('needed'); // needed | pending_review | verified | rejected
            $table->timestamp('uploaded_at')->nullable();
            $table->timestamps();
        });

        Schema::create('commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producer_id')->constrained()->cascadeOnDelete();
            $table->string('policy_reference', 60);
            $table->string('insured_name', 160);
            $table->string('line_of_business', 60);
            $table->date('written_on');
            $table->decimal('premium', 10, 2);
            $table->decimal('commission_base', 10, 2);
            $table->decimal('rate', 5, 4);
            $table->decimal('amount', 10, 2);
            $table->string('status', 20)->default('pending')->index(); // pending | paid
            $table->date('paid_on')->nullable();
            $table->timestamps();
        });

        Schema::table('leads', function (Blueprint $table) {
            $table->foreign('producer_id')->references('id')->on('producers')->nullOnDelete();
            $table->text('producer_notes')->nullable()->after('notes');
            $table->date('follow_up_on')->nullable()->after('producer_notes');
            $table->timestamp('assigned_at')->nullable()->after('producer_id');
        });

        Schema::table('appointments', function (Blueprint $table) {
            $table->foreign('producer_id')->references('id')->on('producers')->nullOnDelete();
        });

        Schema::table('vacation_requests', function (Blueprint $table) {
            $table->decimal('discount_rate', 5, 4)->default(0)->after('payment_amount'); // 10% producer discount
            $table->string('traveller', 20)->nullable()->after('guests'); // producer: Me | Family | Friends
        });
    }

    public function down(): void
    {
        Schema::table('vacation_requests', fn (Blueprint $table) => $table->dropColumn(['discount_rate', 'traveller']));
        Schema::table('appointments', fn (Blueprint $table) => $table->dropForeign(['producer_id']));
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['producer_id']);
            $table->dropColumn(['producer_notes', 'follow_up_on', 'assigned_at']);
        });
        Schema::dropIfExists('commissions');
        Schema::dropIfExists('producer_documents');
        Schema::dropIfExists('producer_licenses');
        Schema::dropIfExists('producers');
    }
};
