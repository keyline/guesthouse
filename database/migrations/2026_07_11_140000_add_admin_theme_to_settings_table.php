<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('admin_sidebar_color', 7)->default('#53647f');
            $table->string('admin_primary_color', 7)->default('#2563eb');
            $table->string('admin_accent_color', 7)->default('#7dd3fc');
        });
    }

    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn([
                'admin_sidebar_color',
                'admin_primary_color',
                'admin_accent_color',
            ]);
        });
    }
};
