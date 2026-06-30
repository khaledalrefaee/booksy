<?php

namespace Database\Factories;

use App\Models\Country;
use Illuminate\Database\Eloquent\Factories\Factory;

class CountryFactory extends Factory
{
    protected $model = Country::class;

    public function definition(): array
    {
        return [
            'name_en' => fake()->country(),
            'name_ar' => fake()->country(),
            'code' => fake()->unique()->countryCode(),
            'dial_code' => '+' . fake()->numberBetween(1, 999),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
