<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('discounts', function (Blueprint $table): void {
            // Optional category scope: null = every room type. Combined with
            // property_id (also nullable) as independent AND filters.
            $table->foreignId('room_type_id')
                ->nullable()
                ->after('property_id')
                ->constrained('room_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('discounts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('room_type_id');
        });
    }
};
