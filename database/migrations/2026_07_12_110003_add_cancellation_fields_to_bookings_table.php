<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            // Immutable copy of the policy taken at booking time, with tier
            // deadlines resolved to absolute datetimes. Refund math reads only
            // this — editing a policy template never changes an existing
            // booking's terms.
            $table->json('cancellation_policy_snapshot')->nullable()->after('cancelled_at');
            $table->string('cancellation_reason')->nullable()->after('cancellation_policy_snapshot');
            $table->string('cancelled_by', 20)->nullable()->after('cancellation_reason');
            $table->unsignedInteger('cancellation_fee_minor')->default(0)->after('cancelled_by');
            $table->unsignedInteger('refund_due_minor')->default(0)->after('cancellation_fee_minor');
            // none | pending | processed | failed | manual — set on the booking
            // that carries the payment; group members stay 'none'.
            $table->string('refund_state', 20)->default('none')->index()->after('refund_due_minor');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table): void {
            $table->dropColumn([
                'cancellation_policy_snapshot',
                'cancellation_reason',
                'cancelled_by',
                'cancellation_fee_minor',
                'refund_due_minor',
                'refund_state',
            ]);
        });
    }
};
