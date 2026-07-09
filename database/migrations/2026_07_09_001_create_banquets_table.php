<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banquets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->integer('capacity_min');
            $table->integer('capacity_max');
            $table->integer('base_price_minor')->default(0);
            $table->string('currency')->default('INR');
            $table->json('setup_types')->nullable();
            $table->json('amenities')->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])->default('draft');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['property_id', 'slug']);
            $table->index('property_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banquets');
    }
};
