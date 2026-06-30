<?php

namespace Database\Factories;

use App\Models\Country;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

class GovernorateFactory extends Factory
{
    protected $model = Governorate::class;

    public function definition(): array
    {
        return [
            'country_id' => Country::factory(),
            'name_en' => fake()->city(),
            'name_ar' => fake()->city(),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
