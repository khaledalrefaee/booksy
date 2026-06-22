<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoOwnerSeeder extends Seeder
{
    /**
     * Sample company, branch, services, staff, and one appointment for the owner panel.
     */
    public function run(): void
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'demo-salon'],
            [
                'sort_order' => 1,
                'name_en' => 'Hair & Beauty',
                'name_ar' => 'تجميل وشعر',
            ]
        );

        $company = Company::query()->firstOrCreate(
            ['email' => 'owner@booksy.demo'],
            [
                'name_en' => 'Demo Salon Co.',
                'name_ar' => 'صالون تجريبي',
                'phone' => '+966500000000',
                'category_id' => $category->id,
                'password' => Hash::make('password'),
                'status' => 'pending',
            ]
        );

        $hairCategory = ServiceCategory::query()->firstOrCreate(
            ['slug' => 'hair-' . $company->id],
            [
                'company_id' => $company->id,
                'name_en' => 'Hair',
                'name_ar' => 'شعر',
                'sort_order' => 1,
            ]
        );

        $groomingCategory = ServiceCategory::query()->firstOrCreate(
            ['slug' => 'grooming-' . $company->id],
            [
                'company_id' => $company->id,
                'name_en' => 'Grooming',
                'name_ar' => 'عناية',
                'sort_order' => 2,
            ]
        );

        $branch = Branch::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name_en' => 'Main branch',
            ],
            [
                'name_ar' => 'الفرع الرئيسي',
                'sort_order' => 0,
                'is_head_office' => true,
                'address' => 'Riyadh',
            ]
        );

        Service::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'name_en' => 'Haircut',
            ],
            [
                'service_category_id' => $hairCategory->id,
                'name_ar' => 'قص شعر',
                'price' => 80,
                'duration_minutes' => 45,
                'is_active' => true,
            ]
        );

        Service::query()->firstOrCreate(
            [
                'branch_id' => $branch->id,
                'name_en' => 'Beard trim',
            ],
            [
                'service_category_id' => $groomingCategory->id,
                'name_ar' => 'تشذيب لحية',
                'price' => 40,
                'duration_minutes' => 20,
                'is_active' => true,
            ]
        );

        $role = Role::query()->where('slug', 'company_owner')->first();
        if ($role) {
            Employee::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'email' => 'manager@booksy.demo',
                ],
                [
                    'branch_id' => $branch->id,
                    'role_id' => $role->id,
                    'name_en' => 'Demo Manager',
                    'name_ar' => 'مدير تجريبي',
                    'password' => Hash::make('password'),
                    'is_active' => true,
                ]
            );
        }

        $customer = \App\Models\Customer::query()->firstOrCreate(
            ['phone' => '0900000000'],
            [
                'name' => 'Demo Client',
            ]
        );

        $service = Service::query()->where('branch_id', $branch->id)->where('name_en', 'Haircut')->first();
        $employee = Employee::query()->where('company_id', $company->id)->first();

        if ($service && $employee && Appointment::query()->where('company_id', $company->id)->doesntExist()) {
            Appointment::query()->create([
                'company_id' => $company->id,
                'branch_id' => $branch->id,
                'customer_id' => $customer->id,
                'employee_id' => $employee->id,
                'service_id' => $service->id,
                'start_time' => now()->addDay()->setHour(10)->setMinute(0)->setSecond(0),
                'end_time' => now()->addDay()->setHour(10)->setMinute(45)->setSecond(0),
                'status' => 'pending',
                'total_price' => $service->price,
                'payment_status' => 'pending',
            ]);
        }
    }
}
