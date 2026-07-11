<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_rates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('rate_plan_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('price_minor');
            $table->unsignedSmallInteger('min_stay')->default(1);
            $table->boolean('closed')->default(false);
            $table->timestamps();

            $table->unique(['rate_plan_id', 'date']);
            $table->index('date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_rates');
    }
};
