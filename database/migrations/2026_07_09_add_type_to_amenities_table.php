<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            if (!Schema::hasColumn('amenities', 'type')) {
                $table->string('type')->default('both')->after('category');
            }
        });
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table) {
            if (Schema::hasColumn('amenities', 'type')) {
                $table->dropColumn('type');
            }
        });
    }
};
