<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Records created from public forms: quote leads, appointments, contact
 * messages, producer applications and claims, plus their uploaded files.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('source', 20)->default('quote'); // quote | home
            $table->string('insurance_type', 40);
            $table->string('zip', 10)->nullable();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email');
            $table->string('phone', 40);
            $table->string('preferred_contact', 20)->nullable();
            $table->string('street')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 2)->nullable();
            $table->text('notes')->nullable();
            $table->json('details')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('producer_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('type', 20); // phone | in_person
            $table->date('preferred_date');
            $table->string('preferred_time', 40);
            $table->string('name', 160);
            $table->string('email');
            $table->string('phone', 40);
            $table->string('reason', 80)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('producer_id')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('contact_messages', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email');
            $table->string('phone', 40)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('new')->index();
            $table->timestamps();
        });

        Schema::create('producer_applications', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email');
            $table->string('phone', 40);
            $table->string('street');
            $table->string('city', 120);
            $table->string('state', 2);
            $table->string('zip', 10);
            $table->string('occupation', 120);
            $table->string('experience', 40);
            $table->string('years_experience', 20);
            $table->string('previous_company')->nullable();
            $table->json('lines_of_business')->nullable();
            $table->string('license_status', 20);
            $table->string('license_number', 60)->nullable();
            $table->string('license_type', 60)->nullable();
            $table->string('license_state', 2)->nullable();
            $table->date('license_issued_on')->nullable();
            $table->date('license_expires_on')->nullable();
            $table->text('reason');
            $table->text('comments')->nullable();
            $table->string('status', 40)->default('submitted')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamp('terms_accepted_at');
            $table->timestamps();
        });

        Schema::create('claims', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email');
            $table->string('phone', 40);
            $table->string('preferred_contact', 20)->nullable();
            $table->string('policy_number', 60);
            $table->string('insurance_type', 40);
            $table->string('claim_type', 60);
            $table->date('incident_date');
            $table->string('incident_time', 10)->nullable();
            $table->string('location');
            $table->text('description');
            $table->json('details')->nullable();
            $table->text('comments')->nullable();
            $table->string('status', 30)->default('submitted')->index();
            $table->timestamps();
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->morphs('attachable');
            $table->string('field', 60);
            $table->string('original_name');
            $table->string('path');
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('claims');
        Schema::dropIfExists('producer_applications');
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('leads');
    }
};
