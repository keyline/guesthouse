<?php

namespace Database\Seeders;

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
    }
}
