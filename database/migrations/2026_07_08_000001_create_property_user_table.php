<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_user', function (Blueprint $table): void {
            $table->foreignId('property_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->primary(['property_id', 'user_id']);
            $table->index(['user_id', 'property_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_user');
    }
};
