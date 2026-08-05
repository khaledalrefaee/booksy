<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        // Real business types (not lorem) so factory-built data never leaks
        // placeholder categories into UI. Prefer CategorySeeder for real data.
        $types = [
            ['Salon', 'صالون'],
            ['Barbershop', 'صالون حلاقة'],
            ['Spa', 'سبا'],
            ['Beauty Center', 'مركز تجميل'],
            ['Nail Studio', 'استوديو أظافر'],
            ['Wellness Center', 'مركز عافية'],
        ];
        $pick = fake()->randomElement($types);

        return [
            'slug' => fake()->unique()->slug(2),
            'name_en' => $pick[0],
            'name_ar' => $pick[1],
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
