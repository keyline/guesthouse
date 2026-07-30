<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('room_types', fn (Blueprint $table) => $table->boolean('is_pet_friendly')->default(false)->after('max_children'));
    }

    public function down(): void
    {
        Schema::table('room_types', fn (Blueprint $table) => $table->dropColumn('is_pet_friendly'));
    }
};
