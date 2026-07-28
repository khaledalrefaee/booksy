<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    public function definition(): array
    {
        $company = Company::factory()->create();

        return [
            'company_id' => $company->id,
            'branch_id' => Branch::factory()->state(['company_id' => $company->id]),
            'role_id' => Role::factory(),
            'name_en' => fake()->name(),
            'name_ar' => fake()->name(),
            'phone' => fake()->unique()->phoneNumber(),
            'email' => fake()->unique()->safeEmail(),
            'is_active' => true,
            'is_bookable' => true,
            /* CONTRACT_TYPES maps key => [label_key, icon, color]; the column
               stores the key, so pick from the keys, not the metadata arrays. */
            'contract_type' => fake()->randomElement(array_keys(Employee::CONTRACT_TYPES)),
            'hire_date' => fake()->date(),
            'password' => 'password',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }

    public function notBookable(): static
    {
        return $this->state(fn() => ['is_bookable' => false]);
    }
}
