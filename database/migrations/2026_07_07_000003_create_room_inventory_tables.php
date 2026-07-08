<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_types', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 40);
            $table->string('status')->default('active')->index();
            $table->unsignedSmallInteger('max_adults')->default(2);
            $table->unsignedSmallInteger('max_children')->default(0);
            $table->unsignedSmallInteger('base_occupancy')->default(2);
            $table->unsignedInteger('base_price_minor')->default(0);
            $table->char('currency', 3)->default('INR');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'code']);
            $table->index(['property_id', 'status']);
        });

        Schema::create('rooms', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->string('room_number', 60);
            $table->string('floor', 60)->nullable();
            $table->string('status')->default('available')->index();
            $table->boolean('is_smoking')->default(false);
            $table->boolean('is_accessible')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['property_id', 'room_number']);
            $table->index(['property_id', 'status']);
            $table->index(['room_type_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('room_types');
    }
};
