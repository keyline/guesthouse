<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('banquet_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('banquet_id')->constrained()->cascadeOnDelete();
            $table->string('season');
            $table->integer('price_per_person');
            $table->date('date_from');
            $table->date('date_to');
            $table->string('description')->nullable();
            $table->timestamps();

            $table->index('banquet_id');
            $table->index(['date_from', 'date_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('banquet_prices');
    }
};
