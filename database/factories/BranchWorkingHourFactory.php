<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\BranchWorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchWorkingHourFactory extends Factory
{
    protected $model = BranchWorkingHour::class;

    public function definition(): array
    {
        return [
            'branch_id' => Branch::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'is_open' => true,
            'open_time' => '09:00',
            'close_time' => '21:00',
            'shift_number' => 1,
        ];
    }
}
