<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->dropUnique(['property_id', 'code']);
            $table->dropIndex(['property_id', 'status']);
            $table->dropConstrainedForeignId('property_id');
            $table->dropColumn(['base_price_minor', 'currency']);
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table): void {
            $table->foreignId('property_id')->nullable()->after('id')->constrained()->nullOnDelete();
            $table->unsignedInteger('base_price_minor')->default(0)->after('base_occupancy');
            $table->char('currency', 3)->default('INR')->after('base_price_minor');
            $table->unique(['property_id', 'code']);
            $table->index(['property_id', 'status']);
        });
    }
};
