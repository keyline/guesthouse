<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('phone', 40)->nullable()->index();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 40)->nullable();
            $table->string('nationality', 120)->nullable();
            $table->string('id_document_type', 80)->nullable();
            $table->string('id_document_number', 120)->nullable();
            $table->text('address')->nullable();
            $table->text('guest_notes')->nullable();
            $table->timestamp('last_booking_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'phone',
                'date_of_birth',
                'gender',
                'nationality',
                'id_document_type',
                'id_document_number',
                'address',
                'guest_notes',
                'last_booking_at',
            ]);
        });
    }
};
