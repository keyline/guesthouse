<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banquet_amenity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banquet_id')->constrained()->cascadeOnDelete();
            $table->foreignId('amenity_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['banquet_id', 'amenity_id']);
            $table->index('banquet_id');
            $table->index('amenity_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banquet_amenity');
    }
};
