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

class DemoCompany2Seeder extends Seeder
{
    public function run(): void
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => 'demo-spa'],
            [
                'sort_order' => 2,
                'name_en' => 'Spa & Wellness',
                'name_ar' => 'سبا وعافية',
            ]
        );

        $company = Company::query()->firstOrCreate(
            ['email' => 'owner2@booksy.demo'],
            [
                'name_en' => 'Luxury Spa Co.',
                'name_ar' => 'سبا فاخر',
                'phone' => '+966500000001',
                'category_id' => $category->id,
                'password' => Hash::make('password'),
                'status' => 'active',
            ]
        );

        $massageCategory = ServiceCategory::query()->firstOrCreate(
            ['slug' => 'massage-' . $company->id],
            [
                'company_id' => $company->id,
                'name_en' => 'Massage',
                'name_ar' => 'تدليك',
                'sort_order' => 1,
            ]
        );

        $facialCategory = ServiceCategory::query()->firstOrCreate(
            ['slug' => 'facial-' . $company->id],
            [
                'company_id' => $company->id,
                'name_en' => 'Facial',
                'name_ar' => 'عناية بالبشرة',
                'sort_order' => 2,
            ]
        );

        $branch = Branch::query()->firstOrCreate(
            [
                'company_id' => $company->id,
                'name_en' => 'Downtown branch',
            ],
            [
                'name_ar' => 'فرع وسط المدينة',
                'sort_order' => 0,
                'is_head_office' => true,
                'address' => 'Jeddah',
            ]
        );

        Service::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'name_en' => 'Deep tissue massage'],
            [
                'service_category_id' => $massageCategory->id,
                'name_ar' => 'تدليك عميق',
                'price' => 200,
                'duration_minutes' => 60,
                'is_active' => true,
            ]
        );

        Service::query()->firstOrCreate(
            ['branch_id' => $branch->id, 'name_en' => 'Facial treatment'],
            [
                'service_category_id' => $facialCategory->id,
                'name_ar' => 'علاج للوجه',
                'price' => 150,
                'duration_minutes' => 45,
                'is_active' => true,
            ]
        );

        $customer = User::query()->firstOrCreate(
            ['email' => 'client2@booksy.demo'],
            [
                'name' => 'Demo Client 2',
                'password' => Hash::make('password'),
            ]
        );

        $service = Service::query()->where('branch_id', $branch->id)->where('name_en', 'Deep tissue massage')->first();

        if ($service && Appointment::query()->where('company_id', $company->id)->doesntExist()) {
            Appointment::query()->create([
                'company_id'     => $company->id,
                'branch_id'      => $branch->id,
                'customer_id'    => $customer->id,
                'service_id'     => $service->id,
                'start_time'     => now()->addDays(2)->setHour(14)->setMinute(0)->setSecond(0),
                'end_time'       => now()->addDays(2)->setHour(15)->setMinute(0)->setSecond(0),
                'status'         => 'confirmed',
                'total_price'    => $service->price,
                'payment_status' => 'pending',
            ]);
        }
    }
}
