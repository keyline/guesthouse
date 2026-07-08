<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('property_type')->default('guest_house')->index();
            $table->string('status')->default('draft')->index();
            $table->string('city')->index();
            $table->string('state')->nullable();
            $table->string('country')->default('India');
            $table->string('postal_code', 20)->nullable();
            $table->text('address');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('manager_name')->nullable();
            $table->unsignedInteger('check_in_time_minutes')->default(720);
            $table->unsignedInteger('check_out_time_minutes')->default(660);
            $table->unsignedInteger('base_price_minor')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->json('policies')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'city']);
            $table->index(['property_type', 'status']);
        });

        Schema::create('amenities', function (Blueprint $table): void {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon')->nullable();
            $table->timestamps();
        });

        Schema::create('amenity_property', function (Blueprint $table): void {
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['amenity_id', 'property_id']);
        });

        Schema::create('property_images', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('alt_text')->nullable();
            $table->boolean('is_primary')->default(false)->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_images');
        Schema::dropIfExists('amenity_property');
        Schema::dropIfExists('amenities');
        Schema::dropIfExists('properties');
    }
};
