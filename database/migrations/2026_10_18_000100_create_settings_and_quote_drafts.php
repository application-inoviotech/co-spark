<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-editable site settings (office details, social links, legal texts)
        Schema::create('settings', function (Blueprint $table) {
            $table->string('key')->primary();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        // SOW: "Retrieve a Saved Quote" — a partially completed quote the visitor can resume
        Schema::create('quote_drafts', function (Blueprint $table) {
            $table->id();
            $table->string('code', 12)->unique();
            $table->string('email');
            $table->json('state');
            $table->unsignedTinyInteger('step')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quote_drafts');
        Schema::dropIfExists('settings');
    }
};
