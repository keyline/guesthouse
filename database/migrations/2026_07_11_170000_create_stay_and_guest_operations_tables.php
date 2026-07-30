<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_guests', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role', 30)->default('additional');
            $table->string('guest_type', 20)->default('adult');
            $table->string('full_name');
            $table->string('phone', 40)->nullable();
            $table->string('email')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality', 80)->default('Indian');
            $table->string('id_verification_status', 20)->default('pending');
            $table->boolean('is_staying')->default(true);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('checked_out_at')->nullable();
            $table->timestamps();
            $table->index(['booking_id', 'role']);
        });

        Schema::create('guest_documents', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('booking_guest_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 30);
            $table->text('document_number_encrypted')->nullable();
            $table->string('document_number_masked', 40)->nullable();
            $table->string('issuing_country', 80)->nullable();
            $table->date('expires_at')->nullable();
            $table->string('front_path')->nullable();
            $table->string('back_path')->nullable();
            $table->string('verification_status', 20)->default('uploaded');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('retention_until')->nullable();
            $table->timestamps();
        });

        Schema::create('stays', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('booking_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status', 30)->default('expected');
            $table->timestamp('actual_check_in_at')->nullable();
            $table->timestamp('actual_check_out_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('checked_out_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('registration_accepted')->default(false);
            $table->text('check_in_notes')->nullable();
            $table->text('check_out_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stay_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('stay_id')->constrained()->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('event_type', 40);
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->index(['stay_id', 'created_at']);
        });

        DB::table('bookings')->orderBy('id')->eachById(function (object $booking): void {
            DB::table('booking_guests')->insert([
                'public_id' => (string) Str::uuid(),
                'booking_id' => $booking->id,
                'user_id' => $booking->user_id,
                'role' => 'primary',
                'guest_type' => 'adult',
                'full_name' => $booking->guest_name,
                'phone' => $booking->guest_phone,
                'email' => $booking->guest_email,
                'nationality' => 'Indian',
                'id_verification_status' => 'pending',
                'is_staying' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stay_events');
        Schema::dropIfExists('stays');
        Schema::dropIfExists('guest_documents');
        Schema::dropIfExists('booking_guests');
    }
};
