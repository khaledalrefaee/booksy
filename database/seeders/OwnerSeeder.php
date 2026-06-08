<?php

namespace Database\Seeders;

use App\Models\Owner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        Owner::firstOrCreate(
            ['email' => 'admin@booksy.com'],
            [
                'name'     => 'Booksy Admin',
                'phone'    => '+966500000001',
                'avatar'   => null,
                'password' => Hash::make('password'),
            ]
        );
    }
}
