<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table): void {
            $table->id();
            $table->string('payment_number')->unique();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            // One payment can settle a multi-room group; linked via the booking group code.
            $table->string('booking_group_code')->nullable()->index();
            $table->string('gateway')->default('razorpay');
            $table->string('gateway_order_id')->nullable()->index();
            $table->string('gateway_payment_id')->nullable()->index();
            $table->string('gateway_signature')->nullable();
            $table->string('gateway_refund_id')->nullable();
            $table->string('method')->nullable();
            $table->string('status')->default('created'); // created, captured, failed, refunded
            $table->unsignedBigInteger('amount_minor');
            $table->unsignedBigInteger('refunded_amount_minor')->default(0);
            $table->string('currency', 3)->default('INR');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });

        Schema::table('bookings', function (Blueprint $table): void {
            $table->unsignedInteger('tax_rate_bp')->default(0)->after('total_amount_minor');
            $table->unsignedBigInteger('tax_amount_minor')->default(0)->after('tax_rate_bp');
            $table->string('payment_status')->default('unpaid')->after('tax_amount_minor'); // unpaid, paid, refunded
            $table->string('invoice_number')->nullable()->unique()->after('payment_status');
        });

        Schema::table('properties', function (Blueprint $table): void {
            $table->string('gstin', 15)->nullable()->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('properties', fn (Blueprint $table) => $table->dropColumn('gstin'));
        Schema::table('bookings', fn (Blueprint $table) => $table->dropColumn(['tax_rate_bp', 'tax_amount_minor', 'payment_status', 'invoice_number']));
        Schema::dropIfExists('payments');
    }
};
