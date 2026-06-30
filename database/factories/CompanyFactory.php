<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

class CompanyFactory extends Factory
{
    protected $model = Company::class;

    public function definition(): array
    {
        return [
            'name_en' => fake()->company(),
            'name_ar' => fake()->company(),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'category_id' => Category::factory(),
            'password' => 'password',
            'status' => 'active',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['status' => 'inactive']);
    }
}
