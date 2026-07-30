<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cancellation_policies', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            // Stable programmatic handle ('flexible', 'non_refundable', …).
            $table->string('code', 40)->unique();
            $table->string('description')->nullable();
            // Ordered tiers: [{"hours_before": 24, "refund_percent": 100}, …].
            // The tier with the largest hours_before that is still ahead of
            // "now" wins; past the last tier the refund is zero.
            $table->json('tiers');
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        $now = now();

        DB::table('cancellation_policies')->insert([
            [
                'name' => 'Flexible',
                'code' => 'flexible',
                'description' => 'Full refund until 24 hours before check-in, half until 6 hours before.',
                'tiers' => json_encode([
                    ['hours_before' => 24, 'refund_percent' => 100],
                    ['hours_before' => 6, 'refund_percent' => 50],
                ]),
                'is_active' => true,
                'sort_order' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Free till day before',
                'code' => 'free_24h',
                'description' => 'Full refund until 24 hours before check-in, nothing after.',
                'tiers' => json_encode([
                    ['hours_before' => 24, 'refund_percent' => 100],
                ]),
                'is_active' => true,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'name' => 'Non-refundable',
                'code' => 'non_refundable',
                'description' => 'No refund on cancellation.',
                'tiers' => json_encode([]),
                'is_active' => true,
                'sort_order' => 2,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_policies');
    }
};
