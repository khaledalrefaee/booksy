<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'service_category_id' => ServiceCategory::factory(),
            'name_en' => fake()->word() . ' Service',
            'name_ar' => 'خدمة ' . fake()->word(),
            'price' => fake()->randomFloat(2, 5000, 50000),
            'currency' => 'SYP',
            'duration_minutes' => fake()->randomElement([15, 30, 45, 60, 90]),
            'is_active' => true,
        ];
    }

    public function withDiscount(string $type = 'percent', float $value = 10): static
    {
        return $this->state(fn() => [
            'discount_type' => $type,
            'discount_value' => $value,
            'discount_starts_at' => now()->subDay(),
            'discount_ends_at' => now()->addWeek(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }
}
