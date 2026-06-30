<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceCategoryFactory extends Factory
{
    protected $model = ServiceCategory::class;

    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'slug' => fake()->unique()->slug(2),
            'name_en' => fake()->word(),
            'name_ar' => fake()->word(),
            'sort_order' => fake()->numberBetween(1, 50),
        ];
    }
}
