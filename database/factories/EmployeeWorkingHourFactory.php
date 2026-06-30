<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\EmployeeWorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeWorkingHourFactory extends Factory
{
    protected $model = EmployeeWorkingHour::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'day_of_week' => fake()->numberBetween(0, 6),
            'is_working' => true,
            'start_time' => '09:00',
            'end_time' => '17:00',
        ];
    }

    public function dayOff(): static
    {
        return $this->state(fn() => ['is_working' => false, 'start_time' => null, 'end_time' => null]);
    }
}
