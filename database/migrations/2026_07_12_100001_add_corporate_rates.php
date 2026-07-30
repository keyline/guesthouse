<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corporates', function (Blueprint $table) {
            // The tie-up: employees type booking_code on the booking page.
            // The blanket discount applies to room types that have no
            // negotiated nightly price in corporate_room_rates.
            $table->string('booking_code', 20)->nullable()->unique()->after('pan');
            $table->string('discount_type', 10)->nullable()->after('booking_code');
            $table->unsignedInteger('discount_value')->nullable()->after('discount_type');
        });

        Schema::create('corporate_room_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('corporate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('price_minor');
            $table->timestamps();

            $table->unique(['corporate_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('corporate_room_rates');

        Schema::table('corporates', function (Blueprint $table) {
            $table->dropColumn(['booking_code', 'discount_type', 'discount_value']);
        });
    }
};
