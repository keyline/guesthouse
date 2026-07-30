<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone_country_code', 6)->nullable();
            $table->string('phone_national', 20)->nullable();
            $table->string('phone_e164', 20)->nullable()->index();
        });

        DB::table('users')->whereNotNull('phone')->orderBy('id')->eachById(function (object $user): void {
            $digits = preg_replace('/\D+/', '', $user->phone);
            if (strlen($digits) === 10) {
                DB::table('users')->where('id', $user->id)->update(['phone_country_code' => '+91', 'phone_national' => $digits, 'phone_e164' => '+91'.$digits]);
            } elseif (str_starts_with($digits, '91') && strlen($digits) === 12) {
                DB::table('users')->where('id', $user->id)->update(['phone_country_code' => '+91', 'phone_national' => substr($digits, 2), 'phone_e164' => '+'.$digits]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['phone_e164']);
            $table->dropColumn(['phone_country_code', 'phone_national', 'phone_e164']);
        });
    }
};
