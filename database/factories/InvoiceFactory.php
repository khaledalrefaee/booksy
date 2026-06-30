<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $company = Company::factory()->create();

        return [
            'invoice_number' => 'INV-' . date('Ymd') . '-' . str_pad(fake()->unique()->numberBetween(1, 9999), 4, '0', STR_PAD_LEFT),
            'company_id' => $company->id,
            'branch_id' => Branch::factory()->state(['company_id' => $company->id]),
            'customer_name' => fake()->name(),
            'customer_phone' => fake()->phoneNumber(),
            'currency' => 'SYP',
            'subtotal' => 25000,
            'discount_amount' => 0,
            'vat_rate' => 0,
            'vat_amount' => 0,
            'total' => 25000,
            'payment_method' => 'cash',
            'status' => 'issued',
            'issued_at' => now(),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn() => ['status' => 'paid', 'paid_at' => now()]);
    }
}
