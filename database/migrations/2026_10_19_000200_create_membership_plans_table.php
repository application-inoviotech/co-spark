<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Admin-editable plans; override config/membership.php and the Stripe price ids at boot
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique(); // free, associate, premium, sub_account
            $table->string('name');
            $table->string('tagline')->nullable();
            $table->string('description')->nullable();
            $table->decimal('monthly_fee', 8, 2)->default(0);
            $table->unsignedSmallInteger('dues_tokens')->default(0);
            $table->decimal('insurance_token_percent', 5, 2)->default(0);
            $table->json('perks')->nullable();
            $table->string('stripe_price_monthly')->nullable();
            $table->string('stripe_price_quarterly')->nullable();
            $table->string('stripe_price_biannual')->nullable();
            $table->string('stripe_price_annual')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
