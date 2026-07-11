<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->boolean('is_online_bookable')->default(false)->after('status');

            $table->index(['property_id', 'is_online_bookable']);
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table): void {
            $table->dropIndex(['property_id', 'is_online_bookable']);
            $table->dropColumn('is_online_bookable');
        });
    }
};
