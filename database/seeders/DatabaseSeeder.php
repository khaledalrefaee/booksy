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
        $this->call([
            CategorySeeder::class,
            LocationsSeeder::class, // essential reference data (Syrian governorates/areas)
            OwnerSeeder::class,
            // Disabled — keep only the base reference data + owner on a fresh DB.
            // DemoOwnerSeeder::class,
            // DemoCompany2Seeder::class,
            // DamascusSeeder::class,
        ]);

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            ['name' => 'Test User', 'password' => \Illuminate\Support\Facades\Hash::make('password')]
        );
    }
}
