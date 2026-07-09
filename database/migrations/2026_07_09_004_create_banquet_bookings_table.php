<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banquet_bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banquet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->string('event_name');
            $table->string('event_type')->nullable();
            $table->string('guest_name');
            $table->string('guest_email');
            $table->string('guest_phone');
            $table->integer('expected_guests');
            $table->string('setup_type')->nullable();
            $table->date('event_date');
            $table->time('event_start_time')->nullable();
            $table->time('event_end_time')->nullable();
            $table->text('special_requirements')->nullable();
            $table->enum('status', ['inquiry', 'confirmed', 'cancelled'])->default('inquiry');
            $table->text('notes')->nullable();
            $table->integer('total_price_minor')->nullable();
            $table->timestamps();

            $table->index('banquet_id');
            $table->index('property_id');
            $table->index('event_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banquet_bookings');
    }
};
