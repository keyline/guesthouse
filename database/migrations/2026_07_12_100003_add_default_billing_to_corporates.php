<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('corporates', function (Blueprint $table) {
            // Who usually settles employee stays: preselects the billing
            // choice on the booking page and the admin booking form.
            $table->string('default_billing', 20)->default('guest')->after('discount_value');
        });
    }

    public function down(): void
    {
        Schema::table('corporates', function (Blueprint $table) {
            $table->dropColumn('default_billing');
        });
    }
};
