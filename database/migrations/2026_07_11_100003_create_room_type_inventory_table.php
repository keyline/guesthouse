<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_type_inventory', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_type_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedSmallInteger('total_rooms')->default(0);
            $table->unsignedSmallInteger('rooms_sold')->default(0);
            $table->boolean('stop_sell')->default(false);
            $table->timestamps();

            $table->unique(['property_id', 'room_type_id', 'date']);
            $table->index(['property_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_type_inventory');
    }
};
