<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_guests', function (Blueprint $table): void {
            $table->string('address_line_1')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('India');
        });

        DB::table('booking_guests')->whereNotNull('user_id')->orderBy('id')->eachById(function (object $guest): void {
            $address = DB::table('users')->where('id', $guest->user_id)->value('address');
            if ($address) {
                DB::table('booking_guests')->where('id', $guest->id)->update(['address_line_1' => $address]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('booking_guests', function (Blueprint $table): void {
            $table->dropColumn(['address_line_1', 'city', 'state', 'postal_code', 'country']);
        });
    }
};
