<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Public website content managed from the admin CMS:
 * insurance categories/products, resources, ecosystem partners and claim types.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('insurance_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('lead')->nullable();
            $table->string('quote_type', 32);
            $table->json('coverage');
            $table->json('options');
            $table->json('benefits');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('insurance_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('insurance_category_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('image_url', 500);
            $table->string('tagline');
            $table->text('intro');
            $table->string('quote_type', 32);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('resource_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->boolean('show_in_filter')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('resources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('resource_category_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('summary', 500);
            $table->string('image_url', 500);
            $table->unsignedTinyInteger('read_minutes')->default(5);
            $table->longText('body')->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('partners', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category');
            $table->string('description', 500);
            $table->string('image_url', 500);
            $table->boolean('is_vacation')->default(false);
            $table->string('location')->nullable();
            $table->json('amenities')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('claim_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->json('immediate_actions');
            $table->json('steps');
            $table->json('documents');
            $table->text('photo_guidance');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('claim_types');
        Schema::dropIfExists('partners');
        Schema::dropIfExists('resources');
        Schema::dropIfExists('resource_categories');
        Schema::dropIfExists('insurance_products');
        Schema::dropIfExists('insurance_categories');
    }
};
