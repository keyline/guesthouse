<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rate_plans', function (Blueprint $table): void {
            $table->foreignId('cancellation_policy_id')
                ->nullable()
                ->after('cancellation_policy')
                ->constrained('cancellation_policies')
                ->nullOnDelete();
        });

        // Backfill from the legacy boolean: refundable plans get the Flexible
        // template, the rest become explicitly non-refundable.
        $policies = DB::table('cancellation_policies')->pluck('id', 'code');

        if (isset($policies['flexible'])) {
            DB::table('rate_plans')->where('is_refundable', true)
                ->update(['cancellation_policy_id' => $policies['flexible']]);
        }

        if (isset($policies['non_refundable'])) {
            DB::table('rate_plans')->where('is_refundable', false)
                ->update(['cancellation_policy_id' => $policies['non_refundable']]);
        }
    }

    public function down(): void
    {
        Schema::table('rate_plans', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('cancellation_policy_id');
        });
    }
};
