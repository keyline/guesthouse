<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->string('category')->default('general')->after('icon')->index();
            $table->boolean('is_active')->default(true)->after('category')->index();
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');
        });

        $now = now();
        $amenities = [
            ['name' => 'Wi-Fi', 'icon' => 'wifi', 'category' => 'connectivity', 'sort_order' => 10],
            ['name' => 'Parking', 'icon' => 'parking', 'category' => 'transport', 'sort_order' => 20],
            ['name' => 'Air Conditioning', 'icon' => 'snowflake', 'category' => 'comfort', 'sort_order' => 30],
            ['name' => 'Restaurant', 'icon' => 'utensils', 'category' => 'food', 'sort_order' => 40],
            ['name' => 'Room Service', 'icon' => 'bell', 'category' => 'service', 'sort_order' => 50],
            ['name' => 'Power Backup', 'icon' => 'bolt', 'category' => 'utility', 'sort_order' => 60],
            ['name' => 'Security', 'icon' => 'shield', 'category' => 'safety', 'sort_order' => 70],
            ['name' => 'Lift', 'icon' => 'lift', 'category' => 'accessibility', 'sort_order' => 80],
            ['name' => 'Laundry', 'icon' => 'laundry', 'category' => 'service', 'sort_order' => 90],
            ['name' => 'TV', 'icon' => 'tv', 'category' => 'entertainment', 'sort_order' => 100],
            ['name' => 'Banquet Hall', 'icon' => 'banquet', 'category' => 'events', 'sort_order' => 110],
            ['name' => 'Swimming Pool', 'icon' => 'pool', 'category' => 'leisure', 'sort_order' => 120],
        ];

        DB::table('amenities')->upsert(
            collect($amenities)->map(fn (array $amenity): array => array_merge($amenity, [
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]))->all(),
            ['name'],
            ['icon', 'category', 'is_active', 'sort_order', 'updated_at']
        );
    }

    public function down(): void
    {
        Schema::table('amenities', function (Blueprint $table): void {
            $table->dropColumn(['category', 'is_active', 'sort_order']);
        });
    }
};
