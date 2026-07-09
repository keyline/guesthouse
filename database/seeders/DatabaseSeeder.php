<?php

namespace Database\Seeders;

use App\Models\Amenity;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->firstOrCreate([
            'email' => env('ADMIN_EMAIL', 'admin@example.com'),
        ], [
            'name' => env('ADMIN_NAME', 'Super Admin'),
            'password' => env('ADMIN_PASSWORD', 'Password#123'),
            'role' => User::ROLE_SUPER_ADMIN,
            'is_active' => true,
        ]);

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

        foreach ($amenities as $amenity) {
            Amenity::query()->updateOrCreate(
                ['name' => $amenity['name']],
                $amenity + ['is_active' => true]
            );
        }
    }
}
