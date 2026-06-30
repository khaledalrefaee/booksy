<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name_en' => fake()->word(),
            'name_ar' => fake()->word(),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
