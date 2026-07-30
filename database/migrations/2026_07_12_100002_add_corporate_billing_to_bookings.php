<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('corporate_id')->nullable()->after('discount_label')
                ->constrained('corporates')->nullOnDelete();
            // Who settles the bill: 'guest' pays as usual, 'corporate' means
            // the stay is invoiced to the company later.
            $table->string('billing', 20)->default('guest')->after('corporate_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corporate_id');
            $table->dropColumn('billing');
        });
    }
};
