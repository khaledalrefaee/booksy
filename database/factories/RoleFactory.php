<?php

namespace Database\Factories;

use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'label_en' => fake()->jobTitle(),
            'label_ar' => fake()->jobTitle(),
        ];
    }
}
