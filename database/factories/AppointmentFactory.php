<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AppointmentFactory extends Factory
{
    protected $model = Appointment::class;

    public function definition(): array
    {
        $company = Company::factory()->create();
        $branch = Branch::factory()->create(['company_id' => $company->id]);
        $startTime = fake()->dateTimeBetween('+1 hour', '+7 days');
        $endTime = (clone $startTime)->modify('+30 minutes');

        return [
            'booking_group_id' => Str::uuid()->toString(),
            'company_id' => $company->id,
            'branch_id' => $branch->id,
            'customer_id' => Customer::factory(),
            'employee_id' => Employee::factory()->state([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
            ]),
            'service_id' => Service::factory()->state(['branch_id' => $branch->id]),
            'start_time' => $startTime,
            'end_time' => $endTime,
            'status' => 'pending',
            'total_price' => fake()->randomFloat(2, 5000, 50000),
            'payment_status' => 'unpaid',
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn() => ['status' => 'confirmed']);
    }

    public function completed(): static
    {
        return $this->state(fn() => ['status' => 'completed', 'payment_status' => 'paid']);
    }

    public function cancelled(): static
    {
        return $this->state(fn() => ['status' => 'cancelled']);
    }
}
