<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name_en' => fake()->company() . ' Branch',
            'name_ar' => 'فرع ' . fake()->word(),
            'status' => 'active',
            'booking_mode' => 'marketplace',
            'slug' => fake()->unique()->slug(3),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'latitude' => fake()->latitude(33, 36),
            'longitude' => fake()->longitude(35, 42),
        ];
    }

    public function private(): static
    {
        return $this->state(fn() => ['booking_mode' => 'private']);
    }
}
