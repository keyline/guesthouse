<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->uuid('booking_group_code')->nullable()->index();
        });
    }
    public function down(): void
    {
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn('booking_group_code'));
    }
};
