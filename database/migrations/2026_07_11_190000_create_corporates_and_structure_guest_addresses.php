<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('corporates', function (Blueprint $table): void {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->string('legal_name');
            $table->string('trade_name')->nullable();
            $table->string('gstin', 15)->unique();
            $table->string('pan', 10)->nullable();
            $table->string('contact_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city', 100);
            $table->string('state', 100);
            $table->string('postal_code', 20);
            $table->string('country', 100)->default('India');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('customer_type', 20)->default('individual');
            $table->foreignId('corporate_id')->nullable()->constrained()->nullOnDelete();
            $table->string('address_line_1')->nullable();
            $table->string('address_line_2')->nullable();
            $table->string('city', 100)->nullable();
            $table->string('state', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('India');
        });

        DB::table('users')->whereNotNull('address')->update(['address_line_1' => DB::raw('address')]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('corporate_id');
            $table->dropColumn(['customer_type', 'address_line_1', 'address_line_2', 'city', 'state', 'postal_code', 'country']);
        });
        Schema::dropIfExists('corporates');
    }
};
