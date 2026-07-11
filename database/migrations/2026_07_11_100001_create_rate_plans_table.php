<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rate_plans', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->string('meal_plan', 10)->default('ep');
            $table->boolean('is_refundable')->default(true);
            $table->text('cancellation_policy')->nullable();
            // Rack-rate fallback used when no daily_rates row exists for a night.
            $table->unsignedInteger('default_price_minor')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->string('status')->default('active')->index();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['property_id', 'room_type_id', 'code']);
            $table->index(['property_id', 'room_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rate_plans');
    }
};
