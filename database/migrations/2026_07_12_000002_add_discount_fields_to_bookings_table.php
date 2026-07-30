<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // total_amount_minor stays the pre-discount tariff; the payable
            // amount is total - discount + tax. discount_label is a snapshot
            // so invoices stay readable after the discount is edited/deleted.
            $table->unsignedInteger('discount_amount_minor')->default(0)->after('total_amount_minor');
            $table->foreignId('discount_id')->nullable()->after('discount_amount_minor')
                ->constrained('discounts')->nullOnDelete();
            $table->string('discount_label')->nullable()->after('discount_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('discount_id');
            $table->dropColumn(['discount_amount_minor', 'discount_label']);
        });
    }
};
