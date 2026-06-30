<?php

namespace Database\Factories;

use App\Models\OtpCode;
use Illuminate\Database\Eloquent\Factories\Factory;

class OtpCodeFactory extends Factory
{
    protected $model = OtpCode::class;

    public function definition(): array
    {
        return [
            'phone' => fake()->unique()->e164PhoneNumber(),
            'code' => str_pad(fake()->numberBetween(0, 9999), 4, '0', STR_PAD_LEFT),
            'expires_at' => now()->addMinutes(4),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn() => ['expires_at' => now()->subMinute()]);
    }

    public function used(): static
    {
        return $this->state(fn() => ['used_at' => now()]);
    }
}
