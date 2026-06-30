<?php

namespace Tests;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Owner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function actingAsOwner(?Owner $owner = null): Owner
    {
        $owner ??= Owner::factory()->create();
        $this->actingAs($owner, 'owner');
        return $owner;
    }

    protected function actingAsCompany(?Company $company = null): Company
    {
        $company ??= Company::factory()->create();
        $this->actingAs($company, 'company');
        return $company;
    }

    protected function actingAsCustomer(?Customer $customer = null): static
    {
        $customer ??= Customer::factory()->create();
        $this->withSession(['customer_id' => $customer->id]);
        return $this;
    }
}
