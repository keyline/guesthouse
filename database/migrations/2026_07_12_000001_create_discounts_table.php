<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One table covers both concepts: a row with a code is a coupon the
        // guest types in; a row without a code is an offer applied by itself.
        Schema::create('discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('code', 40)->nullable()->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('discount_type', 10)->default('percent');
            $table->unsignedInteger('discount_value')->default(0);
            $table->unsignedInteger('max_discount_minor')->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_until')->nullable();
            $table->unsignedSmallInteger('min_nights')->nullable();
            $table->unsignedInteger('min_amount_minor')->nullable();
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('times_used')->default(0);
            $table->string('status', 20)->default('active');
            $table->timestamps();

            $table->index(['property_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discounts');
    }
};
