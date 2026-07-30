<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banquet_leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banquet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('property_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('phone', 40);
            $table->string('email')->nullable();
            $table->string('event_type', 60)->nullable();
            $table->string('event_date', 60)->nullable();
            $table->unsignedInteger('guest_count')->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new');
            $table->timestamps();

            $table->index(['banquet_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banquet_leads');
    }
};
