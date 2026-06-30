<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

class CustomerFactory extends Factory
{
    protected $model = Customer::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'phone' => fake()->unique()->e164PhoneNumber(),
            'age' => fake()->numberBetween(18, 60),
            'phone_verified_at' => now(),
            'tag' => fake()->randomElement(Customer::TAGS),
            'source' => fake()->randomElement(Customer::SOURCES),
            'loyalty_points' => 0,
            'is_banned' => false,
        ];
    }

    public function banned(): static
    {
        return $this->state(fn() => [
            'is_banned' => true,
            'ban_reason' => 'Test ban',
            'banned_at' => now(),
        ]);
    }

    public function unverified(): static
    {
        return $this->state(fn() => ['phone_verified_at' => null]);
    }
}
