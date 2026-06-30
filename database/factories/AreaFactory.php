<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\Governorate;
use Illuminate\Database\Eloquent\Factories\Factory;

class AreaFactory extends Factory
{
    protected $model = Area::class;

    public function definition(): array
    {
        return [
            'governorate_id' => Governorate::factory(),
            'name_en' => fake()->streetName(),
            'name_ar' => fake()->streetName(),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
