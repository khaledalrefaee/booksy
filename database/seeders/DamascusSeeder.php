<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * DamascusSeeder — 25 realistic companies (spas, salons) in Damascus, Syria
 * with 60 branches, employees, services, working hours, and appointments.
 */
class DamascusSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        /* ── Wipe only demo data seeded by this file ── */
        DB::table('appointments')->whereIn('company_id', function ($q) {
            $q->select('id')->from('companies')->where('email', 'like', '%@booksy.test');
        })->delete();
        DB::table('employee_working_hours')->whereIn('employee_id', function ($q) {
            $q->select('id')->from('employees')->whereIn('company_id', function ($q2) {
                $q2->select('id')->from('companies')->where('email', 'like', '%@booksy.test');
            });
        })->delete();
        DB::table('employee_service_categories')->whereIn('employee_id', function ($q) {
            $q->select('id')->from('employees')->whereIn('company_id', function ($q2) {
                $q2->select('id')->from('companies')->where('email', 'like', '%@booksy.test');
            });
        })->delete();
        DB::table('employees')->whereIn('company_id', function ($q) {
            $q->select('id')->from('companies')->where('email', 'like', '%@booksy.test');
        })->delete();
        DB::table('services')->whereIn('branch_id', function ($q) {
            $q->select('id')->from('branches')->whereIn('company_id', function ($q2) {
                $q2->select('id')->from('companies')->where('email', 'like', '%@booksy.test');
            });
        })->delete();
        DB::table('service_categories')->whereIn('company_id', function ($q) {
            $q->select('id')->from('companies')->where('email', 'like', '%@booksy.test');
        })->delete();
        DB::table('branch_working_hours')->whereIn('branch_id', function ($q) {
            $q->select('id')->from('branches')->whereIn('company_id', function ($q2) {
                $q2->select('id')->from('companies')->where('email', 'like', '%@booksy.test');
            });
        })->delete();
        DB::table('branches')->whereIn('company_id', function ($q) {
            $q->select('id')->from('companies')->where('email', 'like', '%@booksy.test');
        })->delete();
        DB::table('companies')->where('email', 'like', '%@booksy.test')->delete();
        DB::table('categories')->whereIn('slug', array_column($this->globalCategories(), 'slug'))->delete();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        /* ════════════════════════════════════════
           1. GLOBAL CATEGORIES
        ════════════════════════════════════════ */
        $catIds = [];
        foreach ($this->globalCategories() as $i => $cat) {
            $catIds[$cat['slug']] = DB::table('categories')->insertGetId([
                'slug'       => $cat['slug'],
                'name_en'    => $cat['name_en'],
                'name_ar'    => $cat['name_ar'],
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        /* ════════════════════════════════════════
           2. COMPANIES (25)
        ════════════════════════════════════════ */
        $companiesData = $this->companiesData();
        $companyIds    = [];

        foreach ($companiesData as $co) {
            $companyIds[$co['key']] = DB::table('companies')->insertGetId([
                'name_en'    => $co['name_en'],
                'name_ar'    => $co['name_ar'],
                'email'      => $co['email'],
                'phone'      => $co['phone'],
                'category_id'=> $catIds[$co['category_slug']],
                'password'   => Hash::make('password'),
                'status'     => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        /* ════════════════════════════════════════
           3. BRANCHES (60), WORKING HOURS, SERVICE CATEGORIES, SERVICES, EMPLOYEES
        ════════════════════════════════════════ */
        $roleId     = $this->ensureRole();
        $customerIds= $this->ensureCustomers();

        foreach ($companiesData as $co) {
            $companyId = $companyIds[$co['key']];

            foreach ($co['branches'] as $bi => $br) {
                /* ── Branch ── */
                $branchId = DB::table('branches')->insertGetId([
                    'company_id'    => $companyId,
                    'name_en'       => $br['name_en'],
                    'name_ar'       => $br['name_ar'],
                    'phone'         => $br['phone'],
                    'address'       => $br['address'],
                    'latitude'      => $br['lat'],
                    'longitude'     => $br['lng'],
                    'is_head_office'=> $bi === 0,
                    'sort_order'    => $bi,
                    'status'        => 'active',
                    'created_at'    => now(),
                    'updated_at'    => now(),
                ]);

                /* ── Working hours ── */
                $this->seedBranchWorkingHours($branchId, $br['schedule']);

                /* ── Service categories for this company ── */
                $svcCatIds = $this->seedServiceCategories($companyId, $co['service_categories']);

                /* ── Services ── */
                $serviceIds = $this->seedServices($branchId, $co['services'], $svcCatIds);

                /* ── Employees ── */
                $empIds = $this->seedEmployees($companyId, $branchId, $roleId, $br['employees'], $co['key']);

                /* ── Employee working hours ── */
                foreach ($empIds as $empId) {
                    $this->seedEmployeeWorkingHours($empId, $br['schedule']);
                }

                /* ── Appointments ── */
                $this->seedAppointments($companyId, $branchId, $empIds, $serviceIds, $customerIds);
            }
        }

        $this->command->info('✅  DamascusSeeder complete — 25 companies, 60 branches seeded.');
    }

    /* ════════════════════════════════════════════════════════════
       DATA DEFINITIONS
    ════════════════════════════════════════════════════════════ */

    private function globalCategories(): array
    {
        return [
            ['slug' => 'spa',            'name_en' => 'Spa & Wellness',      'name_ar' => 'سبا وعافية'],
            ['slug' => 'salon-women',    'name_en' => 'Women\'s Salon',       'name_ar' => 'صالون نسائي'],
            ['slug' => 'salon-men',      'name_en' => 'Men\'s Barber',        'name_ar' => 'صالون رجالي'],
            ['slug' => 'beauty-center',  'name_en' => 'Beauty Center',        'name_ar' => 'مركز تجميل'],
            ['slug' => 'nail-studio',    'name_en' => 'Nail Studio',          'name_ar' => 'استوديو أظافر'],
        ];
    }

    private function companiesData(): array
    {
        /* Damascus neighborhoods and real-ish coordinates */
        return [
            /* ── SPAs ─────────────────────────────────────────── */
            [
                'key'      => 'maya_spa',
                'name_en'  => 'Maya Spa & Wellness',
                'name_ar'  => 'سبا ومركز عافية مايا',
                'email'    => 'maya.spa@booksy.test',
                'phone'    => '+963 11 333 1001',
                'category_slug' => 'spa',
                'service_categories' => [
                    ['slug' => 'maya-massage',  'name_en' => 'Massage Therapy', 'name_ar' => 'العلاج بالتدليك'],
                    ['slug' => 'maya-facial',   'name_en' => 'Facial Treatments','name_ar' => 'علاجات الوجه'],
                    ['slug' => 'maya-body',     'name_en' => 'Body Treatments', 'name_ar' => 'علاجات الجسم'],
                ],
                'services' => [
                    ['name_en' => 'Swedish Massage 60 min',  'name_ar' => 'مساج سويدي 60 دقيقة',    'price' => 45, 'duration' => 60,  'cat' => 0],
                    ['name_en' => 'Hot Stone Massage',        'name_ar' => 'مساج الحجارة الساخنة',     'price' => 65, 'duration' => 90,  'cat' => 0],
                    ['name_en' => 'Deep Tissue Massage',      'name_ar' => 'مساج الأنسجة العميقة',     'price' => 55, 'duration' => 60,  'cat' => 0],
                    ['name_en' => 'Hydrating Facial',         'name_ar' => 'علاج وجه مرطب',            'price' => 40, 'duration' => 45,  'cat' => 1],
                    ['name_en' => 'Anti-Aging Facial',        'name_ar' => 'علاج وجه مضاد للشيخوخة',  'price' => 55, 'duration' => 60,  'cat' => 1],
                    ['name_en' => 'Body Scrub & Wrap',        'name_ar' => 'تقشير وتلفيف الجسم',      'price' => 50, 'duration' => 75,  'cat' => 2],
                    ['name_en' => 'Hammam Classic',           'name_ar' => 'حمام كلاسيكي',             'price' => 35, 'duration' => 60,  'cat' => 2],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Maya Spa — Mezzeh',
                        'name_ar'   => 'سبا مايا — المزة',
                        'phone'     => '+963 11 333 1001',
                        'address'   => 'شارع المزة، بجانب فندق شام، دمشق',
                        'lat'       => 33.5030, 'lng' => 36.2580,
                        'schedule'  => 'full_week',
                        'employees' => ['Lara Haddad', 'Nour Khalil', 'Maya Saleh'],
                    ],
                    [
                        'name_en'   => 'Maya Spa — Malki',
                        'name_ar'   => 'سبا مايا — المالكي',
                        'phone'     => '+963 11 333 1002',
                        'address'   => 'شارع أبو رمانة، المالكي، دمشق',
                        'lat'       => 33.5133, 'lng' => 36.2794,
                        'schedule'  => 'full_week',
                        'employees' => ['Rim Nassar', 'Dina Barakat'],
                    ],
                ],
            ],
            [
                'key'      => 'orient_spa',
                'name_en'  => 'Orient Spa Damascus',
                'name_ar'  => 'أوريانت سبا دمشق',
                'email'    => 'orient.spa@booksy.test',
                'phone'    => '+963 11 444 2001',
                'category_slug' => 'spa',
                'service_categories' => [
                    ['slug' => 'orient-massage', 'name_en' => 'Oriental Massage', 'name_ar' => 'المساج الشرقي'],
                    ['slug' => 'orient-hammam',  'name_en' => 'Hammam & Steam',   'name_ar' => 'حمام وبخار'],
                    ['slug' => 'orient-body',    'name_en' => 'Body Care',        'name_ar' => 'العناية بالجسم'],
                ],
                'services' => [
                    ['name_en' => 'Oriental Oil Massage',    'name_ar' => 'مساج زيوت شرقي',          'price' => 50, 'duration' => 60,  'cat' => 0],
                    ['name_en' => 'Moroccan Hammam',          'name_ar' => 'حمام مغربي',               'price' => 40, 'duration' => 75,  'cat' => 1],
                    ['name_en' => 'Steam & Exfoliation',      'name_ar' => 'بخار وتقشير',              'price' => 30, 'duration' => 45,  'cat' => 1],
                    ['name_en' => 'Argan Oil Body Wrap',      'name_ar' => 'تلفيف جسم بزيت الأرغان', 'price' => 55, 'duration' => 60,  'cat' => 2],
                    ['name_en' => 'Foot Reflexology',         'name_ar' => 'انعكاس القدم',             'price' => 25, 'duration' => 30,  'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Orient Spa — Old Damascus',
                        'name_ar'   => 'أوريانت سبا — دمشق القديمة',
                        'phone'     => '+963 11 444 2001',
                        'address'   => 'حي باب توما، دمشق القديمة',
                        'lat'       => 33.5118, 'lng' => 36.3142,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Ahmad Zaher', 'Khalid Moussa'],
                    ],
                    [
                        'name_en'   => 'Orient Spa — Yarmouk',
                        'name_ar'   => 'أوريانت سبا — اليرموك',
                        'phone'     => '+963 11 444 2002',
                        'address'   => 'شارع اليرموك الرئيسي، دمشق',
                        'lat'       => 33.4836, 'lng' => 36.2971,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Samer Diab', 'Omar Nassar'],
                    ],
                ],
            ],
            [
                'key'      => 'lotus_spa',
                'name_en'  => 'Lotus Spa & Beauty',
                'name_ar'  => 'لوتس سبا وتجميل',
                'email'    => 'lotus.spa@booksy.test',
                'phone'    => '+963 11 555 3001',
                'category_slug' => 'spa',
                'service_categories' => [
                    ['slug' => 'lotus-relax',   'name_en' => 'Relaxation',   'name_ar' => 'الاسترخاء'],
                    ['slug' => 'lotus-beauty',  'name_en' => 'Beauty',       'name_ar' => 'التجميل'],
                ],
                'services' => [
                    ['name_en' => 'Full Body Relaxation',    'name_ar' => 'استرخاء كامل للجسم',      'price' => 60, 'duration' => 90,  'cat' => 0],
                    ['name_en' => 'Couple Massage',           'name_ar' => 'مساج للزوجين',             'price' => 110,'duration' => 60,  'cat' => 0],
                    ['name_en' => 'Vitamin C Facial',         'name_ar' => 'علاج وجه بفيتامين سي',    'price' => 45, 'duration' => 45,  'cat' => 1],
                    ['name_en' => 'Eyebrow Threading',        'name_ar' => 'تشقير الحواجب',            'price' => 8,  'duration' => 15,  'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Lotus Spa — Kafarsouseh',
                        'name_ar'   => 'لوتس سبا — كفرسوسة',
                        'phone'     => '+963 11 555 3001',
                        'address'   => 'شارع الثلاثين، كفرسوسة، دمشق',
                        'lat'       => 33.4905, 'lng' => 36.2631,
                        'schedule'  => 'full_week',
                        'employees' => ['Hana Ibrahim', 'Sana Mustafa', 'Rania Fares'],
                    ],
                ],
            ],

            /* ── WOMEN'S SALONS ────────────────────────────────── */
            [
                'key'      => 'farah_salon',
                'name_en'  => 'Farah Beauty Salon',
                'name_ar'  => 'صالون فرح للتجميل',
                'email'    => 'farah.salon@booksy.test',
                'phone'    => '+963 11 222 4001',
                'category_slug' => 'salon-women',
                'service_categories' => [
                    ['slug' => 'farah-hair',  'name_en' => 'Hair',         'name_ar' => 'الشعر'],
                    ['slug' => 'farah-skin',  'name_en' => 'Skin & Face',  'name_ar' => 'البشرة والوجه'],
                    ['slug' => 'farah-nails', 'name_en' => 'Nails',        'name_ar' => 'الأظافر'],
                    ['slug' => 'farah-makeup','name_en' => 'Makeup',       'name_ar' => 'المكياج'],
                ],
                'services' => [
                    ['name_en' => 'Haircut & Blowdry',       'name_ar' => 'قص وتصفيف الشعر',          'price' => 20, 'duration' => 45,  'cat' => 0],
                    ['name_en' => 'Hair Color (Full)',        'name_ar' => 'صبغ شعر كامل',             'price' => 50, 'duration' => 120, 'cat' => 0],
                    ['name_en' => 'Highlights & Balayage',   'name_ar' => 'هايلايت وبلاياج',          'price' => 70, 'duration' => 150, 'cat' => 0],
                    ['name_en' => 'Keratin Treatment',       'name_ar' => 'علاج كيراتين',             'price' => 90, 'duration' => 180, 'cat' => 0],
                    ['name_en' => 'Bridal Makeup',           'name_ar' => 'مكياج عرائس',              'price' => 80, 'duration' => 90,  'cat' => 3],
                    ['name_en' => 'Evening Makeup',          'name_ar' => 'مكياج سهرة',               'price' => 45, 'duration' => 60,  'cat' => 3],
                    ['name_en' => 'Manicure',                'name_ar' => 'مانيكير',                  'price' => 15, 'duration' => 30,  'cat' => 2],
                    ['name_en' => 'Pedicure',                'name_ar' => 'باديكير',                  'price' => 18, 'duration' => 40,  'cat' => 2],
                    ['name_en' => 'Eyebrow Shaping',         'name_ar' => 'تشكيل الحواجب',            'price' => 10, 'duration' => 20,  'cat' => 1],
                    ['name_en' => 'Face Wax',                'name_ar' => 'شمع الوجه',                'price' => 12, 'duration' => 20,  'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Farah Salon — Rawda',
                        'name_ar'   => 'صالون فرح — الروضة',
                        'phone'     => '+963 11 222 4001',
                        'address'   => 'شارع الروضة، بالقرب من حديقة تشرين، دمشق',
                        'lat'       => 33.5068, 'lng' => 36.2843,
                        'schedule'  => 'full_week',
                        'employees' => ['Farah Saad', 'Mona Tawil', 'Dalia Khoury', 'Samar Abi Nader'],
                    ],
                    [
                        'name_en'   => 'Farah Salon — Qudsaya',
                        'name_ar'   => 'صالون فرح — قدسيا',
                        'phone'     => '+963 11 222 4002',
                        'address'   => 'الشارع الرئيسي، قدسيا، ريف دمشق',
                        'lat'       => 33.5460, 'lng' => 36.2290,
                        'schedule'  => 'full_week',
                        'employees' => ['Hiba Rifai', 'Salam Darwish'],
                    ],
                    [
                        'name_en'   => 'Farah Salon — Jaramana',
                        'name_ar'   => 'صالون فرح — جرمانا',
                        'phone'     => '+963 11 222 4003',
                        'address'   => 'شارع الأمين، جرمانا، ريف دمشق',
                        'lat'       => 33.4910, 'lng' => 36.3410,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Nadia Suleiman', 'Rana Haj Ali'],
                    ],
                ],
            ],
            [
                'key'      => 'nour_beauty',
                'name_en'  => 'Nour Beauty Center',
                'name_ar'  => 'مركز نور للتجميل',
                'email'    => 'nour.beauty@booksy.test',
                'phone'    => '+963 11 222 5001',
                'category_slug' => 'salon-women',
                'service_categories' => [
                    ['slug' => 'nour-hair',   'name_en' => 'Hair Services',   'name_ar' => 'خدمات الشعر'],
                    ['slug' => 'nour-body',   'name_en' => 'Body & Waxing',   'name_ar' => 'الجسم والشمع'],
                    ['slug' => 'nour-lashes', 'name_en' => 'Lashes & Brows',  'name_ar' => 'رموش وحواجب'],
                ],
                'services' => [
                    ['name_en' => 'Women\'s Haircut',        'name_ar' => 'قص شعر نسائي',             'price' => 18, 'duration' => 40,  'cat' => 0],
                    ['name_en' => 'Blow Dry',                'name_ar' => 'تمليس الشعر',               'price' => 12, 'duration' => 30,  'cat' => 0],
                    ['name_en' => 'Hair Color + Highlights', 'name_ar' => 'صبغة وهايلايت',            'price' => 65, 'duration' => 150, 'cat' => 0],
                    ['name_en' => 'Eyelash Extensions',      'name_ar' => 'تمديد رموش',               'price' => 35, 'duration' => 90,  'cat' => 2],
                    ['name_en' => 'Eyebrow Microblading',    'name_ar' => 'رسم حواجب',                'price' => 60, 'duration' => 90,  'cat' => 2],
                    ['name_en' => 'Full Body Wax',           'name_ar' => 'إزالة شعر كامل',          'price' => 40, 'duration' => 60,  'cat' => 1],
                    ['name_en' => 'Upper Lip Wax',           'name_ar' => 'شمع الشارب',               'price' => 5,  'duration' => 10,  'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Nour Beauty — Barzeh',
                        'name_ar'   => 'نور للتجميل — برزة',
                        'phone'     => '+963 11 222 5001',
                        'address'   => 'شارع برزة البلد، دمشق',
                        'lat'       => 33.5392, 'lng' => 36.3066,
                        'schedule'  => 'full_week',
                        'employees' => ['Nour Azzam', 'Raneem Sleiman', 'Wafa Khatib'],
                    ],
                    [
                        'name_en'   => 'Nour Beauty — Kaboun',
                        'name_ar'   => 'نور للتجميل — القابون',
                        'phone'     => '+963 11 222 5002',
                        'address'   => 'شارع القابون، دمشق',
                        'lat'       => 33.5298, 'lng' => 36.3337,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Iman Bakri', 'Layan Saad'],
                    ],
                ],
            ],
            [
                'key'      => 'damas_glamour',
                'name_en'  => 'Damas Glamour',
                'name_ar'  => 'داماس غلامور',
                'email'    => 'damas.glamour@booksy.test',
                'phone'    => '+963 11 333 6001',
                'category_slug' => 'salon-women',
                'service_categories' => [
                    ['slug' => 'dg-hair',   'name_en' => 'Hair',        'name_ar' => 'الشعر'],
                    ['slug' => 'dg-face',   'name_en' => 'Face',        'name_ar' => 'الوجه'],
                    ['slug' => 'dg-henna',  'name_en' => 'Henna Art',   'name_ar' => 'فن الحناء'],
                ],
                'services' => [
                    ['name_en' => 'Bridal Hair & Makeup',    'name_ar' => 'شعر ومكياج عروس',          'price' => 120,'duration' => 180, 'cat' => 0],
                    ['name_en' => 'Hair Extensions',          'name_ar' => 'وصلات شعر',                'price' => 100,'duration' => 120, 'cat' => 0],
                    ['name_en' => 'Henna Bridal Design',      'name_ar' => 'حناء عروس',                'price' => 50, 'duration' => 120, 'cat' => 2],
                    ['name_en' => 'Simple Henna',             'name_ar' => 'حناء بسيطة',               'price' => 15, 'duration' => 30,  'cat' => 2],
                    ['name_en' => 'Facial Cleansing',         'name_ar' => 'تنظيف البشرة',             'price' => 30, 'duration' => 45,  'cat' => 1],
                    ['name_en' => 'Highlights (Partial)',     'name_ar' => 'هايلايت جزئي',             'price' => 40, 'duration' => 90,  'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Damas Glamour — Midan',
                        'name_ar'   => 'داماس غلامور — الميدان',
                        'phone'     => '+963 11 333 6001',
                        'address'   => 'شارع الميدان الرئيسي، دمشق',
                        'lat'       => 33.4875, 'lng' => 36.2956,
                        'schedule'  => 'full_week',
                        'employees' => ['Ghada Sharif', 'Mais Hamdan', 'Laila Zeno'],
                    ],
                    [
                        'name_en'   => 'Damas Glamour — Abbasiyin',
                        'name_ar'   => 'داماس غلامور — العباسيين',
                        'phone'     => '+963 11 333 6002',
                        'address'   => 'قرب دوار العباسيين، دمشق',
                        'lat'       => 33.5221, 'lng' => 36.3120,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Abeer Mansour', 'Tala Aslan'],
                    ],
                ],
            ],
            [
                'key'      => 'reem_salon',
                'name_en'  => 'Reem Ladies Salon',
                'name_ar'  => 'صالون ريم للسيدات',
                'email'    => 'reem.salon@booksy.test',
                'phone'    => '+963 11 444 7001',
                'category_slug' => 'salon-women',
                'service_categories' => [
                    ['slug' => 'reem-hair',  'name_en' => 'Hair', 'name_ar' => 'الشعر'],
                    ['slug' => 'reem-care',  'name_en' => 'Care', 'name_ar' => 'العناية'],
                ],
                'services' => [
                    ['name_en' => 'Haircut',              'name_ar' => 'قص شعر',           'price' => 15, 'duration' => 30, 'cat' => 0],
                    ['name_en' => 'Root Touch-Up',        'name_ar' => 'صبغة جذور',         'price' => 25, 'duration' => 60, 'cat' => 0],
                    ['name_en' => 'Hair Mask Treatment',  'name_ar' => 'ماسك للشعر',        'price' => 20, 'duration' => 45, 'cat' => 0],
                    ['name_en' => 'Eyebrow Tinting',      'name_ar' => 'صبغة حواجب',        'price' => 8,  'duration' => 15, 'cat' => 1],
                    ['name_en' => 'Underarm Wax',         'name_ar' => 'شمع إبط',           'price' => 7,  'duration' => 15, 'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Reem Salon — Rukn Eddin',
                        'name_ar'   => 'صالون ريم — ركن الدين',
                        'phone'     => '+963 11 444 7001',
                        'address'   => 'شارع ركن الدين، دمشق',
                        'lat'       => 33.5312, 'lng' => 36.2878,
                        'schedule'  => 'full_week',
                        'employees' => ['Reem Othman', 'Sara Khalid', 'Doha Nimer'],
                    ],
                ],
            ],
            [
                'key'      => 'shams_beauty',
                'name_en'  => 'Shams Beauty House',
                'name_ar'  => 'بيت شمس للتجميل',
                'email'    => 'shams.beauty@booksy.test',
                'phone'    => '+963 11 222 8001',
                'category_slug' => 'beauty-center',
                'service_categories' => [
                    ['slug' => 'shams-laser',   'name_en' => 'Laser & Light', 'name_ar' => 'الليزر والضوء'],
                    ['slug' => 'shams-skin',    'name_en' => 'Skin Care',     'name_ar' => 'العناية بالبشرة'],
                    ['slug' => 'shams-slimming','name_en' => 'Slimming',      'name_ar' => 'التنحيف'],
                ],
                'services' => [
                    ['name_en' => 'Laser Hair Removal (Legs)',  'name_ar' => 'إزالة شعر بالليزر (رجلان)', 'price' => 70, 'duration' => 45, 'cat' => 0],
                    ['name_en' => 'Laser Hair Removal (Face)',  'name_ar' => 'إزالة شعر بالليزر (وجه)',   'price' => 30, 'duration' => 20, 'cat' => 0],
                    ['name_en' => 'HydraFacial',                'name_ar' => 'هيدرافيشل',                 'price' => 80, 'duration' => 60, 'cat' => 1],
                    ['name_en' => 'Mesotherapy',                'name_ar' => 'ميزوثيرابي',                'price' => 90, 'duration' => 60, 'cat' => 1],
                    ['name_en' => 'Cavitation Slimming',        'name_ar' => 'كافيتيشن للتنحيف',          'price' => 55, 'duration' => 45, 'cat' => 2],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Shams Beauty — Qassa',
                        'name_ar'   => 'شمس للتجميل — القصاع',
                        'phone'     => '+963 11 222 8001',
                        'address'   => 'حي القصاع، دمشق',
                        'lat'       => 33.5196, 'lng' => 36.3022,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Dr. Aya Hassan', 'Mira Jundi', 'Hala Nuri'],
                    ],
                    [
                        'name_en'   => 'Shams Beauty — Kafarsouseh',
                        'name_ar'   => 'شمس للتجميل — كفرسوسة',
                        'phone'     => '+963 11 222 8002',
                        'address'   => 'كفرسوسة، شارع الزهراء، دمشق',
                        'lat'       => 33.4942, 'lng' => 36.2588,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Rana Mourad', 'Lama Yousef'],
                    ],
                ],
            ],

            /* ── MEN'S BARBERS ─────────────────────────────────── */
            [
                'key'      => 'albasma_barber',
                'name_en'  => 'Al-Basma Barbershop',
                'name_ar'  => 'حلاقة البسمة',
                'email'    => 'albasma.barber@booksy.test',
                'phone'    => '+963 11 555 9001',
                'category_slug' => 'salon-men',
                'service_categories' => [
                    ['slug' => 'basma-hair',  'name_en' => 'Haircut',       'name_ar' => 'الحلاقة'],
                    ['slug' => 'basma-beard', 'name_en' => 'Beard',         'name_ar' => 'اللحية'],
                    ['slug' => 'basma-care',  'name_en' => 'Grooming',      'name_ar' => 'العناية'],
                ],
                'services' => [
                    ['name_en' => 'Haircut',                  'name_ar' => 'قص شعر',                   'price' => 8,  'duration' => 20, 'cat' => 0],
                    ['name_en' => 'Haircut + Beard',          'name_ar' => 'قص شعر + لحية',            'price' => 14, 'duration' => 35, 'cat' => 0],
                    ['name_en' => 'Fade Haircut',             'name_ar' => 'قصة فيد',                  'price' => 12, 'duration' => 30, 'cat' => 0],
                    ['name_en' => 'Beard Trim & Shape',       'name_ar' => 'تشكيل اللحية',             'price' => 8,  'duration' => 20, 'cat' => 1],
                    ['name_en' => 'Straight Razor Shave',     'name_ar' => 'حلاقة بالموس',             'price' => 10, 'duration' => 25, 'cat' => 1],
                    ['name_en' => 'Hair Color (Men)',         'name_ar' => 'صبغة رجالي',               'price' => 20, 'duration' => 40, 'cat' => 0],
                    ['name_en' => 'Facial Wax',               'name_ar' => 'شمع وجه',                  'price' => 6,  'duration' => 15, 'cat' => 2],
                    ['name_en' => 'Scalp Massage',            'name_ar' => 'مساج فروة الرأس',          'price' => 7,  'duration' => 15, 'cat' => 2],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Al-Basma — Shaalan',
                        'name_ar'   => 'البسمة — الشعلان',
                        'phone'     => '+963 11 555 9001',
                        'address'   => 'شارع الشعلان، دمشق',
                        'lat'       => 33.5083, 'lng' => 36.2751,
                        'schedule'  => 'fri_closed',
                        'employees' => ['Ammar Qasim', 'Hassan Ziyad', 'Fadi Shamoun'],
                    ],
                    [
                        'name_en'   => 'Al-Basma — Mohajireen',
                        'name_ar'   => 'البسمة — المهاجرين',
                        'phone'     => '+963 11 555 9002',
                        'address'   => 'حي المهاجرين، دمشق',
                        'lat'       => 33.5215, 'lng' => 36.2729,
                        'schedule'  => 'fri_closed',
                        'employees' => ['Rami Badawi', 'Wael Jaber'],
                    ],
                    [
                        'name_en'   => 'Al-Basma — Dmar',
                        'name_ar'   => 'البسمة — دمر',
                        'phone'     => '+963 11 555 9003',
                        'address'   => 'دمر، شارع المدارس، ريف دمشق',
                        'lat'       => 33.5240, 'lng' => 36.2118,
                        'schedule'  => 'fri_closed',
                        'employees' => ['Tariq Hamwi', 'Nidal Issa'],
                    ],
                ],
            ],
            [
                'key'      => 'crown_barber',
                'name_en'  => 'Crown Barbershop',
                'name_ar'  => 'حلاقة كراون',
                'email'    => 'crown.barber@booksy.test',
                'phone'    => '+963 11 444 1001',
                'category_slug' => 'salon-men',
                'service_categories' => [
                    ['slug' => 'crown-cut',   'name_en' => 'Cuts',       'name_ar' => 'قصات'],
                    ['slug' => 'crown-beard', 'name_en' => 'Beard',      'name_ar' => 'لحية'],
                    ['slug' => 'crown-pkg',   'name_en' => 'Packages',   'name_ar' => 'باقات'],
                ],
                'services' => [
                    ['name_en' => 'Classic Haircut',          'name_ar' => 'قصة كلاسيكية',             'price' => 10, 'duration' => 25, 'cat' => 0],
                    ['name_en' => 'Skin Fade',                'name_ar' => 'فيد جلدي',                 'price' => 15, 'duration' => 35, 'cat' => 0],
                    ['name_en' => 'Beard Lineup',             'name_ar' => 'تحديد اللحية',             'price' => 7,  'duration' => 15, 'cat' => 1],
                    ['name_en' => 'Full Beard Grooming',      'name_ar' => 'عناية كاملة باللحية',      'price' => 12, 'duration' => 25, 'cat' => 1],
                    ['name_en' => 'VIP Package (Cut+Beard+Facial)','name_ar'=> 'باقة VIP',             'price' => 35, 'duration' => 75, 'cat' => 2],
                    ['name_en' => 'Kid\'s Haircut',           'name_ar' => 'قصة أطفال',               'price' => 7,  'duration' => 20, 'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Crown Barber — Malki',
                        'name_ar'   => 'كراون — المالكي',
                        'phone'     => '+963 11 444 1001',
                        'address'   => 'شارع المالكي، بجانب كافيه روتانا، دمشق',
                        'lat'       => 33.5160, 'lng' => 36.2815,
                        'schedule'  => 'fri_closed',
                        'employees' => ['Majd Khoury', 'Yousuf Nawfal', 'Samer Ali'],
                    ],
                    [
                        'name_en'   => 'Crown Barber — Dwela',
                        'name_ar'   => 'كراون — الدويلعة',
                        'phone'     => '+963 11 444 1002',
                        'address'   => 'الدويلعة، دمشق',
                        'lat'       => 33.5005, 'lng' => 36.3240,
                        'schedule'  => 'fri_closed',
                        'employees' => ['Bassam Hasan', 'Karam Salim'],
                    ],
                ],
            ],
            [
                'key'      => 'gentleman_barber',
                'name_en'  => 'The Gentleman Barbershop',
                'name_ar'  => 'حلاقة الجنتلمان',
                'email'    => 'gentleman.barber@booksy.test',
                'phone'    => '+963 11 333 1101',
                'category_slug' => 'salon-men',
                'service_categories' => [
                    ['slug' => 'gent-style', 'name_en' => 'Style',      'name_ar' => 'التصفيف'],
                    ['slug' => 'gent-beard', 'name_en' => 'Beard Care', 'name_ar' => 'العناية باللحية'],
                ],
                'services' => [
                    ['name_en' => 'Textured Crop',       'name_ar' => 'قصة كروب',              'price' => 12, 'duration' => 30, 'cat' => 0],
                    ['name_en' => 'Undercut',            'name_ar' => 'قصة أندركات',           'price' => 14, 'duration' => 30, 'cat' => 0],
                    ['name_en' => 'Hot Towel Shave',     'name_ar' => 'حلاقة فوطة ساخنة',     'price' => 12, 'duration' => 30, 'cat' => 1],
                    ['name_en' => 'Beard Oil Treatment', 'name_ar' => 'علاج زيت للحية',       'price' => 8,  'duration' => 15, 'cat' => 1],
                    ['name_en' => 'Full Groom Package',  'name_ar' => 'باقة العناية الكاملة', 'price' => 30, 'duration' => 60, 'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en'   => 'The Gentleman — Abu Rummaneh',
                        'name_ar'   => 'الجنتلمان — أبو رمانة',
                        'phone'     => '+963 11 333 1101',
                        'address'   => 'شارع أبو رمانة، دمشق',
                        'lat'       => 33.5148, 'lng' => 36.2860,
                        'schedule'  => 'fri_closed',
                        'employees' => ['Ziad Haddad', 'Raed Qabbani'],
                    ],
                ],
            ],
            [
                'key'      => 'damascus_barber',
                'name_en'  => 'Damascus Heritage Barber',
                'name_ar'  => 'حلاقة دمشق التراثية',
                'email'    => 'heritage.barber@booksy.test',
                'phone'    => '+963 11 555 1201',
                'category_slug' => 'salon-men',
                'service_categories' => [
                    ['slug' => 'heritage-trad', 'name_en' => 'Traditional',  'name_ar' => 'تقليدي'],
                    ['slug' => 'heritage-mod',  'name_en' => 'Modern',       'name_ar' => 'حديث'],
                ],
                'services' => [
                    ['name_en' => 'Traditional Syrian Shave', 'name_ar' => 'حلاقة سورية تقليدية', 'price' => 8,  'duration' => 30, 'cat' => 0],
                    ['name_en' => 'Classic Men Haircut',      'name_ar' => 'قصة رجالي كلاسيك',   'price' => 7,  'duration' => 20, 'cat' => 0],
                    ['name_en' => 'Modern Fade + Design',     'name_ar' => 'فيد حديث ورسمة',     'price' => 18, 'duration' => 40, 'cat' => 1],
                    ['name_en' => 'Henna Beard Color',        'name_ar' => 'صبغة حناء للحية',    'price' => 10, 'duration' => 30, 'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en'   => 'Heritage Barber — Old City',
                        'name_ar'   => 'الحلاقة التراثية — المدينة القديمة',
                        'phone'     => '+963 11 555 1201',
                        'address'   => 'سوق الحميدية، دمشق القديمة',
                        'lat'       => 33.5110, 'lng' => 36.3060,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Abu Khaled Masri', 'Yasser Hallaq'],
                    ],
                    [
                        'name_en'   => 'Heritage Barber — Sarouja',
                        'name_ar'   => 'الحلاقة التراثية — ساروجة',
                        'phone'     => '+963 11 555 1202',
                        'address'   => 'حي ساروجة، دمشق',
                        'lat'       => 33.5078, 'lng' => 36.3088,
                        'schedule'  => 'fri_sat_closed',
                        'employees' => ['Nasser Ghazal'],
                    ],
                ],
            ],

            /* ── MORE COMPANIES to reach 25 ─────────────────────── */
            [
                'key' => 'royal_nails', 'name_en' => 'Royal Nail Studio',
                'name_ar' => 'استوديو رويال للأظافر',
                'email' => 'royal.nails@booksy.test', 'phone' => '+963 11 222 1301',
                'category_slug' => 'nail-studio',
                'service_categories' => [
                    ['slug' => 'rn-nails', 'name_en' => 'Nail Art', 'name_ar' => 'فن الأظافر'],
                    ['slug' => 'rn-care',  'name_en' => 'Nail Care', 'name_ar' => 'العناية بالأظافر'],
                ],
                'services' => [
                    ['name_en' => 'Gel Manicure',       'name_ar' => 'مانيكير جل',        'price' => 22, 'duration' => 45, 'cat' => 0],
                    ['name_en' => 'Acrylic Nails',      'name_ar' => 'أظافر أكريليك',     'price' => 35, 'duration' => 60, 'cat' => 0],
                    ['name_en' => 'Nail Art Design',    'name_ar' => 'رسم على الأظافر',   'price' => 15, 'duration' => 30, 'cat' => 0],
                    ['name_en' => 'Spa Pedicure',       'name_ar' => 'باديكير سبا',       'price' => 25, 'duration' => 50, 'cat' => 1],
                    ['name_en' => 'Nail Repair',        'name_ar' => 'إصلاح أظافر',      'price' => 5,  'duration' => 10, 'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en' => 'Royal Nails — Mezzeh 86', 'name_ar' => 'رويال نيلز — المزة 86',
                        'phone' => '+963 11 222 1301', 'address' => 'المزة 86، دمشق',
                        'lat' => 33.4980, 'lng' => 36.2510, 'schedule' => 'full_week',
                        'employees' => ['Kristy Saad', 'Lulu Barakat'],
                    ],
                    [
                        'name_en' => 'Royal Nails — Tijara', 'name_ar' => 'رويال نيلز — التجارة',
                        'phone' => '+963 11 222 1302', 'address' => 'منطقة التجارة، دمشق',
                        'lat' => 33.5050, 'lng' => 36.2920, 'schedule' => 'full_week',
                        'employees' => ['Nadia Jwayed'],
                    ],
                ],
            ],
            [
                'key' => 'silk_spa', 'name_en' => 'Silk Spa Damascus',
                'name_ar' => 'سيلك سبا دمشق',
                'email' => 'silk.spa@booksy.test', 'phone' => '+963 11 333 1401',
                'category_slug' => 'spa',
                'service_categories' => [
                    ['slug' => 'silk-massage', 'name_en' => 'Massage', 'name_ar' => 'مساج'],
                    ['slug' => 'silk-beauty',  'name_en' => 'Beauty',  'name_ar' => 'تجميل'],
                ],
                'services' => [
                    ['name_en' => 'Thai Massage',        'name_ar' => 'مساج تايلاندي',     'price' => 55, 'duration' => 60,  'cat' => 0],
                    ['name_en' => 'Lomi Lomi Massage',   'name_ar' => 'مساج لومي لومي',    'price' => 65, 'duration' => 75,  'cat' => 0],
                    ['name_en' => 'Silk Facial',         'name_ar' => 'فيشيال حرير',       'price' => 50, 'duration' => 60,  'cat' => 1],
                    ['name_en' => 'Gold Facial',         'name_ar' => 'فيشيال بالذهب',     'price' => 75, 'duration' => 75,  'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en' => 'Silk Spa — Arnous', 'name_ar' => 'سيلك سبا — العرنوس',
                        'phone' => '+963 11 333 1401', 'address' => 'ساحة العرنوس، دمشق',
                        'lat' => 33.5069, 'lng' => 36.2953, 'schedule' => 'full_week',
                        'employees' => ['Diana Khoury', 'Nada Makhoul', 'Selin Aziz'],
                    ],
                ],
            ],
            [
                'key' => 'petra_salon', 'name_en' => 'Petra Beauty Salon',
                'name_ar' => 'صالون بترا للتجميل',
                'email' => 'petra.salon@booksy.test', 'phone' => '+963 11 444 1501',
                'category_slug' => 'salon-women',
                'service_categories' => [
                    ['slug' => 'petra-hair',   'name_en' => 'Hair',     'name_ar' => 'شعر'],
                    ['slug' => 'petra-makeup', 'name_en' => 'Makeup',   'name_ar' => 'مكياج'],
                ],
                'services' => [
                    ['name_en' => 'Ombre Hair Color',  'name_ar' => 'صبغة أومبري',     'price' => 60, 'duration' => 120, 'cat' => 0],
                    ['name_en' => 'Brazilian Blowout', 'name_ar' => 'بلو-أوت برازيلي','price' => 85, 'duration' => 150, 'cat' => 0],
                    ['name_en' => 'Party Makeup',      'name_ar' => 'مكياج حفلات',     'price' => 40, 'duration' => 60,  'cat' => 1],
                    ['name_en' => 'Natural Makeup',    'name_ar' => 'مكياج طبيعي',     'price' => 30, 'duration' => 45,  'cat' => 1],
                    ['name_en' => 'Hair Trim',         'name_ar' => 'تشذيب الأطراف',   'price' => 10, 'duration' => 20,  'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en' => 'Petra Salon — Dummar', 'name_ar' => 'بترا — ضمير/دمر',
                        'phone' => '+963 11 444 1501', 'address' => 'دمر الجديدة، دمشق',
                        'lat' => 33.5320, 'lng' => 36.2095, 'schedule' => 'full_week',
                        'employees' => ['Petra Nassar', 'Celine Ghanem', 'Mirna Rahhal'],
                    ],
                    [
                        'name_en' => 'Petra Salon — Qatana Rd', 'name_ar' => 'بترا — طريق قطنا',
                        'phone' => '+963 11 444 1502', 'address' => 'طريق قطنا، ريف دمشق',
                        'lat' => 33.4760, 'lng' => 36.0850, 'schedule' => 'fri_sat_closed',
                        'employees' => ['Lara Nassar', 'Aya Wakim'],
                    ],
                ],
            ],
            [
                'key' => 'tigers_barber', 'name_en' => 'Tiger Cuts Barbershop',
                'name_ar' => 'حلاقة تايغر كاتس',
                'email' => 'tiger.barber@booksy.test', 'phone' => '+963 11 555 1601',
                'category_slug' => 'salon-men',
                'service_categories' => [
                    ['slug' => 'tiger-cuts',  'name_en' => 'Haircuts', 'name_ar' => 'قصات'],
                    ['slug' => 'tiger-beard', 'name_en' => 'Beard',    'name_ar' => 'لحية'],
                ],
                'services' => [
                    ['name_en' => 'Drop Fade',         'name_ar' => 'دروب فيد',        'price' => 13, 'duration' => 30, 'cat' => 0],
                    ['name_en' => 'Pompadour',         'name_ar' => 'بومبادور',        'price' => 15, 'duration' => 35, 'cat' => 0],
                    ['name_en' => 'Line Up',           'name_ar' => 'لاين أب',         'price' => 6,  'duration' => 15, 'cat' => 0],
                    ['name_en' => 'Full Beard Shape',  'name_ar' => 'تشكيل لحية كامل','price' => 10, 'duration' => 25, 'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en' => 'Tiger Cuts — Harasta', 'name_ar' => 'تايغر — حرستا',
                        'phone' => '+963 11 555 1601', 'address' => 'حرستا، ريف دمشق',
                        'lat' => 33.5625, 'lng' => 36.3688, 'schedule' => 'fri_closed',
                        'employees' => ['Mahdi Kurdi', 'Louay Sabbagh'],
                    ],
                ],
            ],
            [
                'key' => 'venus_spa', 'name_en' => 'Venus Spa & Body',
                'name_ar' => 'سبا فينوس',
                'email' => 'venus.spa@booksy.test', 'phone' => '+963 11 333 1701',
                'category_slug' => 'spa',
                'service_categories' => [
                    ['slug' => 'venus-relax', 'name_en' => 'Relaxation', 'name_ar' => 'الاسترخاء'],
                    ['slug' => 'venus-skin',  'name_en' => 'Skin',       'name_ar' => 'البشرة'],
                ],
                'services' => [
                    ['name_en' => 'Aromatherapy Massage', 'name_ar' => 'مساج بالزيوت العطرية', 'price' => 50, 'duration' => 60, 'cat' => 0],
                    ['name_en' => 'Bamboo Massage',       'name_ar' => 'مساج بالخيزران',        'price' => 60, 'duration' => 75, 'cat' => 0],
                    ['name_en' => 'Ozone Facial',         'name_ar' => 'فيشيال أوزون',          'price' => 45, 'duration' => 50, 'cat' => 1],
                    ['name_en' => 'Back Massage',         'name_ar' => 'مساج الظهر',            'price' => 30, 'duration' => 30, 'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en' => 'Venus Spa — Bab Musalla', 'name_ar' => 'فينوس سبا — باب مصلى',
                        'phone' => '+963 11 333 1701', 'address' => 'باب مصلى، دمشق',
                        'lat' => 33.4980, 'lng' => 36.3085, 'schedule' => 'full_week',
                        'employees' => ['Helen Youssef', 'Marlene Azar'],
                    ],
                ],
            ],
            [
                'key' => 'kenz_salon', 'name_en' => 'Kenz Ladies Salon',
                'name_ar' => 'صالون كنز للسيدات',
                'email' => 'kenz.salon@booksy.test', 'phone' => '+963 11 222 1801',
                'category_slug' => 'salon-women',
                'service_categories' => [
                    ['slug' => 'kenz-hair',  'name_en' => 'Hair',  'name_ar' => 'شعر'],
                    ['slug' => 'kenz-nails', 'name_en' => 'Nails', 'name_ar' => 'أظافر'],
                    ['slug' => 'kenz-brows', 'name_en' => 'Brows', 'name_ar' => 'حواجب'],
                ],
                'services' => [
                    ['name_en' => 'Haircut & Style',     'name_ar' => 'قص وتصفيف',            'price' => 20, 'duration' => 45, 'cat' => 0],
                    ['name_en' => 'Semi-Permanent Color','name_ar' => 'صبغة شبه دائمة',       'price' => 35, 'duration' => 90, 'cat' => 0],
                    ['name_en' => 'Classic Manicure',    'name_ar' => 'مانيكير كلاسيكي',      'price' => 12, 'duration' => 25, 'cat' => 1],
                    ['name_en' => 'Gel Pedicure',        'name_ar' => 'باديكير جل',            'price' => 22, 'duration' => 45, 'cat' => 1],
                    ['name_en' => 'Brow Lamination',     'name_ar' => 'لمينيشن حواجب',        'price' => 28, 'duration' => 45, 'cat' => 2],
                ],
                'branches' => [
                    [
                        'name_en' => 'Kenz Salon — Kafar Batna', 'name_ar' => 'كنز — كفر بطنا',
                        'phone' => '+963 11 222 1801', 'address' => 'كفر بطنا، ريف دمشق الشرقي',
                        'lat' => 33.5505, 'lng' => 36.3832, 'schedule' => 'fri_sat_closed',
                        'employees' => ['Kenz Hariri', 'Sana Hasan', 'Amal Jabi'],
                    ],
                ],
            ],
            [
                'key' => 'topline_barber', 'name_en' => 'Topline Barbers',
                'name_ar' => 'توبلاين للحلاقة',
                'email' => 'topline.barber@booksy.test', 'phone' => '+963 11 444 1901',
                'category_slug' => 'salon-men',
                'service_categories' => [
                    ['slug' => 'top-cuts', 'name_en' => 'Cuts',     'name_ar' => 'قصات'],
                    ['slug' => 'top-pkg',  'name_en' => 'Packages', 'name_ar' => 'باقات'],
                ],
                'services' => [
                    ['name_en' => 'Simple Haircut',   'name_ar' => 'قصة بسيطة',          'price' => 8,  'duration' => 20, 'cat' => 0],
                    ['name_en' => 'Fade + Beard',     'name_ar' => 'فيد + لحية',          'price' => 16, 'duration' => 40, 'cat' => 0],
                    ['name_en' => 'Men Spa Package',  'name_ar' => 'باقة رجالي سبا',     'price' => 40, 'duration' => 80, 'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en' => 'Topline — Zablatani', 'name_ar' => 'توبلاين — الزبلطاني',
                        'phone' => '+963 11 444 1901', 'address' => 'الزبلطاني، دمشق',
                        'lat' => 33.5175, 'lng' => 36.3195, 'schedule' => 'fri_closed',
                        'employees' => ['Ihab Ramadan', 'Ghassan Barakat', 'Mustafa Eid'],
                    ],
                ],
            ],
            [
                'key' => 'aurora_beauty', 'name_en' => 'Aurora Beauty Center',
                'name_ar' => 'مركز أورورا للتجميل',
                'email' => 'aurora.beauty@booksy.test', 'phone' => '+963 11 333 2001',
                'category_slug' => 'beauty-center',
                'service_categories' => [
                    ['slug' => 'aurora-laser', 'name_en' => 'Laser',      'name_ar' => 'ليزر'],
                    ['slug' => 'aurora-face',  'name_en' => 'Face Lift',  'name_ar' => 'شد الوجه'],
                ],
                'services' => [
                    ['name_en' => 'Diode Laser Leg',   'name_ar' => 'ليزر ديود رجلين',    'price' => 80, 'duration' => 50, 'cat' => 0],
                    ['name_en' => 'RF Face Lift',      'name_ar' => 'شد وجه بالـ RF',     'price' => 95, 'duration' => 60, 'cat' => 1],
                    ['name_en' => 'Chemical Peel',     'name_ar' => 'تقشير كيميائي',      'price' => 55, 'duration' => 40, 'cat' => 1],
                    ['name_en' => 'Filler Lips',       'name_ar' => 'فيلر شفاه',          'price' => 120,'duration' => 30, 'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en' => 'Aurora — Mazzeh Villas', 'name_ar' => 'أورورا — فيلات المزة',
                        'phone' => '+963 11 333 2001', 'address' => 'فيلات المزة، دمشق',
                        'lat' => 33.5010, 'lng' => 36.2455, 'schedule' => 'fri_sat_closed',
                        'employees' => ['Dr. Lina Salim', 'Rola Hamza'],
                    ],
                ],
            ],
            [
                'key' => 'nails_station', 'name_en' => 'Nails Station Damascus',
                'name_ar' => 'نيلز ستيشن دمشق',
                'email' => 'nails.station@booksy.test', 'phone' => '+963 11 222 2101',
                'category_slug' => 'nail-studio',
                'service_categories' => [
                    ['slug' => 'ns-gel',    'name_en' => 'Gel Nails',   'name_ar' => 'أظافر جل'],
                    ['slug' => 'ns-deco',   'name_en' => 'Decoration',  'name_ar' => 'ديكور'],
                ],
                'services' => [
                    ['name_en' => 'Gel Polish',          'name_ar' => 'طلاء جل',             'price' => 18, 'duration' => 35, 'cat' => 0],
                    ['name_en' => 'Builder Gel',         'name_ar' => 'جل بناء',             'price' => 30, 'duration' => 55, 'cat' => 0],
                    ['name_en' => 'Chrome Powder Nails', 'name_ar' => 'أظافر كروم',          'price' => 25, 'duration' => 45, 'cat' => 1],
                    ['name_en' => '3D Nail Art',         'name_ar' => 'رسم ثلاثي الأبعاد',  'price' => 20, 'duration' => 40, 'cat' => 1],
                    ['name_en' => 'Removal & Redo',      'name_ar' => 'إزالة وإعادة',        'price' => 22, 'duration' => 50, 'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en' => 'Nails Station — Midan', 'name_ar' => 'نيلز ستيشن — الميدان',
                        'phone' => '+963 11 222 2101', 'address' => 'شارع الميدان، دمشق',
                        'lat' => 33.4863, 'lng' => 36.2980, 'schedule' => 'full_week',
                        'employees' => ['Nour Jamal', 'Sandy Azhari'],
                    ],
                    [
                        'name_en' => 'Nails Station — Bab Sharqi', 'name_ar' => 'نيلز ستيشن — باب شرقي',
                        'phone' => '+963 11 222 2102', 'address' => 'باب شرقي، دمشق القديمة',
                        'lat' => 33.5105, 'lng' => 36.3200, 'schedule' => 'fri_sat_closed',
                        'employees' => ['Lana Attar'],
                    ],
                ],
            ],
            [
                'key' => 'jasmine_spa', 'name_en' => 'Jasmine Spa',
                'name_ar' => 'سبا الياسمين',
                'email' => 'jasmine.spa@booksy.test', 'phone' => '+963 11 444 2201',
                'category_slug' => 'spa',
                'service_categories' => [
                    ['slug' => 'jas-massage', 'name_en' => 'Massage', 'name_ar' => 'مساج'],
                    ['slug' => 'jas-relax',   'name_en' => 'Relax',   'name_ar' => 'استرخاء'],
                ],
                'services' => [
                    ['name_en' => 'Jasmine Oil Massage', 'name_ar' => 'مساج زيت الياسمين', 'price' => 48, 'duration' => 60, 'cat' => 0],
                    ['name_en' => 'Stress Relief Massage','name_ar' => 'مساج إزالة التعب', 'price' => 42, 'duration' => 50, 'cat' => 0],
                    ['name_en' => 'Detox Body Wrap',      'name_ar' => 'تلفيف جسم ديتوكس', 'price' => 55, 'duration' => 70, 'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en' => 'Jasmine Spa — Jisr Abyad', 'name_ar' => 'سبا الياسمين — الجسر الأبيض',
                        'phone' => '+963 11 444 2201', 'address' => 'شارع الجسر الأبيض، دمشق',
                        'lat' => 33.5232, 'lng' => 36.2968, 'schedule' => 'full_week',
                        'employees' => ['Jasmine Kurdi', 'Rana Akkad', 'Sara Houri'],
                    ],
                ],
            ],
            [
                'key' => 'elite_barber', 'name_en' => 'Elite Barbershop',
                'name_ar' => 'إيليت للحلاقة',
                'email' => 'elite.barber@booksy.test', 'phone' => '+963 11 555 2301',
                'category_slug' => 'salon-men',
                'service_categories' => [
                    ['slug' => 'elite-cuts',  'name_en' => 'Cuts',   'name_ar' => 'قصات'],
                    ['slug' => 'elite-beard', 'name_en' => 'Beard',  'name_ar' => 'لحية'],
                ],
                'services' => [
                    ['name_en' => 'Buzz Cut',          'name_ar' => 'قصة باز',            'price' => 7,  'duration' => 15, 'cat' => 0],
                    ['name_en' => 'Scissor Cut',       'name_ar' => 'قصة بالمقص',         'price' => 12, 'duration' => 30, 'cat' => 0],
                    ['name_en' => 'Beard Shaping',     'name_ar' => 'تشكيل لحية',         'price' => 8,  'duration' => 20, 'cat' => 1],
                    ['name_en' => 'Kid Cut',           'name_ar' => 'قصة أطفال',          'price' => 6,  'duration' => 15, 'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en' => 'Elite — Sidi Amoud', 'name_ar' => 'إيليت — سيدي عمود',
                        'phone' => '+963 11 555 2301', 'address' => 'منطقة سيدي عمود، دمشق',
                        'lat' => 33.5090, 'lng' => 36.3155, 'schedule' => 'fri_closed',
                        'employees' => ['Fares Ismail', 'Bilal Khouri'],
                    ],
                ],
            ],
            [
                'key' => 'stars_beauty', 'name_en' => 'Stars Beauty Lounge',
                'name_ar' => 'ستارز بيوتي لاونج',
                'email' => 'stars.beauty@booksy.test', 'phone' => '+963 11 333 2401',
                'category_slug' => 'salon-women',
                'service_categories' => [
                    ['slug' => 'stars-hair',   'name_en' => 'Hair',     'name_ar' => 'شعر'],
                    ['slug' => 'stars-facial', 'name_en' => 'Facials',  'name_ar' => 'عناية بشرة'],
                    ['slug' => 'stars-makeup', 'name_en' => 'Makeup',   'name_ar' => 'مكياج'],
                ],
                'services' => [
                    ['name_en' => 'Luxury Blow Dry',   'name_ar' => 'تصفيف فاخر',         'price' => 18, 'duration' => 35, 'cat' => 0],
                    ['name_en' => 'Pearl Facial',      'name_ar' => 'فيشيال اللؤلؤ',      'price' => 60, 'duration' => 60, 'cat' => 1],
                    ['name_en' => 'Full Glam Makeup',  'name_ar' => 'مكياج غلام كامل',    'price' => 55, 'duration' => 75, 'cat' => 2],
                    ['name_en' => 'Eyelash Lift',      'name_ar' => 'رفع الرموش',         'price' => 30, 'duration' => 45, 'cat' => 1],
                ],
                'branches' => [
                    [
                        'name_en' => 'Stars Beauty — Quneitra Rd', 'name_ar' => 'ستارز — طريق القنيطرة',
                        'phone' => '+963 11 333 2401', 'address' => 'طريق القنيطرة، دمشق',
                        'lat' => 33.5072, 'lng' => 36.2400, 'schedule' => 'full_week',
                        'employees' => ['Nelly Qassab', 'Rasha Suleiman', 'Maya Chaar'],
                    ],
                ],
            ],
            [
                'key' => 'serene_spa2', 'name_en' => 'Serene Wellness Spa',
                'name_ar' => 'سبا سيرين للعافية',
                'email' => 'serene.spa@booksy.test', 'phone' => '+963 11 444 2501',
                'category_slug' => 'spa',
                'service_categories' => [
                    ['slug' => 'sw-deep',  'name_en' => 'Deep Tissue', 'name_ar' => 'أنسجة عميقة'],
                    ['slug' => 'sw-sport', 'name_en' => 'Sports',      'name_ar' => 'رياضي'],
                ],
                'services' => [
                    ['name_en' => 'Sports Massage',      'name_ar' => 'مساج رياضي',         'price' => 50, 'duration' => 60, 'cat' => 1],
                    ['name_en' => 'Deep Tissue 90 min',  'name_ar' => 'أنسجة عميقة 90 دقيقة','price' => 75, 'duration' => 90, 'cat' => 0],
                    ['name_en' => 'Cupping Therapy',     'name_ar' => 'حجامة',               'price' => 35, 'duration' => 45, 'cat' => 0],
                    ['name_en' => 'Reflexology 45 min',  'name_ar' => 'انعكاس 45 دقيقة',    'price' => 28, 'duration' => 45, 'cat' => 0],
                ],
                'branches' => [
                    [
                        'name_en' => 'Serene Spa — Jobar', 'name_ar' => 'سيرين سبا — جوبر',
                        'phone' => '+963 11 444 2501', 'address' => 'جوبر، دمشق',
                        'lat' => 33.5261, 'lng' => 36.3282, 'schedule' => 'fri_sat_closed',
                        'employees' => ['Mark Daher', 'Farid Shibli'],
                    ],
                ],
            ],
        ];
    }

    /* ════════════════════════════════════════════════════════════
       HELPERS
    ════════════════════════════════════════════════════════════ */

    private function seedBranchWorkingHours(int $branchId, string $schedule): void
    {
        $shifts = $this->scheduleShifts($schedule);
        foreach ($shifts as $row) {
            DB::table('branch_working_hours')->insert(array_merge($row, [
                'branch_id'    => $branchId,
                'shift_number' => 1,
                'created_at'   => now(),
                'updated_at'   => now(),
            ]));
        }
    }

    private function scheduleShifts(string $schedule): array
    {
        /* day_of_week: 0=Sun, 1=Mon … 6=Sat */
        $full   = ['09:00:00', '21:00:00'];
        $short  = ['10:00:00', '18:00:00'];
        $closed = null;

        $map = match ($schedule) {
            'full_week'      => [0 => $full,  1 => $full,  2 => $full,  3 => $full,  4 => $full,  5 => $full,  6 => $full],
            'fri_closed'     => [0 => $full,  1 => $full,  2 => $full,  3 => $full,  4 => $full,  5 => $closed,6 => $full],
            'fri_sat_closed' => [0 => $full,  1 => $full,  2 => $full,  3 => $full,  4 => $full,  5 => $closed,6 => $closed],
            default          => [0 => $full,  1 => $full,  2 => $full,  3 => $full,  4 => $full,  5 => $full,  6 => $full],
        };

        $rows = [];
        foreach ($map as $day => $hours) {
            $rows[] = [
                'day_of_week' => $day,
                'is_open'     => $hours !== null ? 1 : 0,
                'open_time'   => $hours[0] ?? null,
                'close_time'  => $hours[1] ?? null,
            ];
        }
        return $rows;
    }

    private function seedServiceCategories(int $companyId, array $cats): array
    {
        $ids = [];
        foreach ($cats as $i => $cat) {
            /* fully unique slug: original-slug + company_id + microsecond suffix */
            $slug = $cat['slug'] . '-c' . $companyId . '-' . substr(str_replace('.', '', microtime(true)), -6);
            $ids[] = DB::table('service_categories')->insertGetId([
                'company_id' => $companyId,
                'slug'       => $slug,
                'name_en'    => $cat['name_en'],
                'name_ar'    => $cat['name_ar'],
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $ids;
    }

    private function seedServices(int $branchId, array $services, array $svcCatIds): array
    {
        $ids = [];
        foreach ($services as $svc) {
            $catId = $svcCatIds[$svc['cat']] ?? $svcCatIds[0];
            $ids[] = DB::table('services')->insertGetId([
                'branch_id'           => $branchId,
                'service_category_id' => $catId,
                'name_en'             => $svc['name_en'],
                'name_ar'             => $svc['name_ar'],
                'price'               => $svc['price'],
                'duration_minutes'    => $svc['duration'],
                'is_active'           => 1,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);
        }
        return $ids;
    }

    private function seedEmployees(int $companyId, int $branchId, int $roleId, array $names, string $coKey): array
    {
        $ids = [];
        foreach ($names as $i => $name) {
            $email = Str::slug($name) . '.' . $coKey . '@booksy.test';
            /* skip if email already exists for this company */
            $existing = DB::table('employees')
                ->where('company_id', $companyId)
                ->where('email', $email)
                ->value('id');
            if ($existing) { $ids[] = $existing; continue; }

            $ids[] = DB::table('employees')->insertGetId([
                'company_id' => $companyId,
                'branch_id'  => $branchId,
                'role_id'    => $roleId,
                'name_ar'    => $name,
                'name_en'    => $name,
                'email'      => $email,
                'password'   => Hash::make('password'),
                'is_active'  => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $ids;
    }

    private function seedEmployeeWorkingHours(int $empId, string $schedule): void
    {
        $shifts = $this->scheduleShifts($schedule);
        foreach ($shifts as $row) {
            DB::table('employee_working_hours')->insertOrIgnore([
                'employee_id' => $empId,
                'day_of_week' => $row['day_of_week'],
                'is_working'  => $row['is_open'],
                'start_time'  => $row['open_time'],
                'end_time'    => $row['close_time'],
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        }
    }

    private function seedAppointments(int $companyId, int $branchId, array $empIds, array $serviceIds, array $customerIds): void
    {
        if (empty($empIds) || empty($serviceIds) || empty($customerIds)) return;

        $statuses = ['pending','confirmed','confirmed','completed','completed','completed','cancelled','no_show'];

        /* Seed appointments spread over last 30 days and next 14 days */
        for ($i = 0; $i < 18; $i++) {
            $daysOffset  = rand(-30, 14);
            $hour        = rand(9, 19);
            $minute      = [0, 15, 30, 45][rand(0, 3)];
            $startTime   = now()->addDays($daysOffset)->setHour($hour)->setMinute($minute)->setSecond(0);
            $serviceId   = $serviceIds[array_rand($serviceIds)];
            $duration    = DB::table('services')->where('id', $serviceId)->value('duration_minutes') ?? 30;
            $endTime     = (clone $startTime)->addMinutes($duration);
            $status      = $statuses[array_rand($statuses)];
            $price       = DB::table('services')->where('id', $serviceId)->value('price') ?? 0;

            DB::table('appointments')->insert([
                'company_id'    => $companyId,
                'branch_id'     => $branchId,
                'customer_id'   => $customerIds[array_rand($customerIds)],
                'employee_id'   => $empIds[array_rand($empIds)],
                'service_id'    => $serviceId,
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'status'        => $status,
                'payment_status'=> $status === 'completed' ? 'paid' : 'pending',
                'total_price'   => $price,
                'created_at'    => now(),
                'updated_at'    => now(),
            ]);
        }
    }

    private function ensureRole(): int
    {
        return DB::table('roles')->where('slug', 'employee')->value('id')
            ?? DB::table('roles')->insertGetId([
                'slug'       => 'employee',
                'label_en'   => 'Employee',
                'label_ar'   => 'موظف',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function ensureCustomers(): array
    {
        $customers = [
            ['name' => 'ليلى أحمد',      'email' => 'layla.ahmed@test.com'],
            ['name' => 'سارة محمود',     'email' => 'sara.mahmoud@test.com'],
            ['name' => 'ريم الخالد',     'email' => 'reem.khaled@test.com'],
            ['name' => 'نور الدين',      'email' => 'nour.aldin@test.com'],
            ['name' => 'ياسمين حسن',    'email' => 'yasmine.hasan@test.com'],
            ['name' => 'مياس سليمان',   'email' => 'mias.suleiman@test.com'],
            ['name' => 'رنا شريف',       'email' => 'rana.sharif@test.com'],
            ['name' => 'تالا الأسد',     'email' => 'tala.asad@test.com'],
            ['name' => 'كريم يوسف',     'email' => 'karim.yousuf@test.com'],
            ['name' => 'أحمد الزهراوي','email' => 'ahmad.zahrawai@test.com'],
            ['name' => 'محمد الحلبي',   'email' => 'mohammad.halabi@test.com'],
            ['name' => 'عمر النصر',     'email' => 'omar.nasr@test.com'],
            ['name' => 'بشار قنواتي',   'email' => 'bashar.qanawati@test.com'],
            ['name' => 'دانا عكاوي',    'email' => 'dana.akkawi@test.com'],
            ['name' => 'هبة الجمل',     'email' => 'heba.jamal@test.com'],
        ];

        $ids = [];
        foreach ($customers as $c) {
            $existing = DB::table('users')->where('email', $c['email'])->value('id');
            if ($existing) { $ids[] = $existing; continue; }
            $ids[] = DB::table('users')->insertGetId([
                'name'       => $c['name'],
                'email'      => $c['email'],
                'password'   => Hash::make('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        return $ids;
    }
}
