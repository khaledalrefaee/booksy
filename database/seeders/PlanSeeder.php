<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $off = collect(Plan::featureKeys())->mapWithKeys(fn ($k) => [$k => false]);

        $plans = [
            [
                'name_en'       => 'Free',
                'name_ar'       => 'مجانية',
                'price'         => 0,
                'currency'      => 'USD',
                'duration_days' => 365,
                'max_branches'  => 1,
                'max_employees' => 5,
                'sort_order'    => 1,
                'features'      => $off->merge([
                    'whatsapp' => true,
                ])->all(),
            ],
            [
                'name_en'       => 'Basic',
                'name_ar'       => 'الأساسية',
                'price'         => 5,
                'currency'      => 'USD',
                'duration_days' => 30,
                'max_branches'  => 2,
                'max_employees' => 15,
                'sort_order'    => 2,
                'features'      => $off->merge([
                    'whatsapp'        => true,
                    'leaves'          => true,
                    'attendance'      => true,
                    'waitlist'        => true,
                    'loyalty'         => true,
                    'private_booking' => true,
                ])->all(),
            ],
            [
                'name_en'       => 'Pro',
                'name_ar'       => 'الاحترافية',
                'price'         => 15,
                'currency'      => 'USD',
                'duration_days' => 30,
                'max_branches'  => null,
                'max_employees' => null,
                'sort_order'    => 3,
                'features'      => $off->map(fn () => true)->all(),
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(
                ['name_en' => $plan['name_en']],
                $plan + ['is_active' => true],
            );
        }
    }
}
