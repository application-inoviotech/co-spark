<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name', 80);
            $table->string('last_name', 80);
            $table->string('email')->unique();
            $table->string('phone', 40)->nullable();
            $table->string('street')->nullable();
            $table->string('street2')->nullable();
            $table->string('city', 120)->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip', 10)->nullable();
            $table->string('role', 20)->default('member')->index(); // member | producer | admin
            $table->string('status', 20)->default('pending')->index(); // pending | active | suspended
            $table->boolean('sms_opt_in')->default(false);
            $table->timestamp('sms_opt_in_at')->nullable();
            $table->string('avatar_path')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('users')->nullOnDelete(); // Premium sub-accounts
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
