<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * PERFORMANCE / LOAD TEST SEEDER (throwaway).
 *
 * Fills one company with heavy, realistic data (many employees, services,
 * customers and tens of thousands of appointments) plus large employee photos,
 * so we can measure how the company dashboard and list pages behave at scale.
 *
 * Everything it creates is TAGGED so it can be removed cleanly:
 *   - customers.source            = 'perf_seed'
 *   - employees.national_id       = 'PERFSEED'
 *   - services.description        = 'PERF_SEED'
 *   - service_categories.slug     LIKE 'perfseed-%'
 *   - branches.slug               LIKE 'perfseed-%'
 *   - appointments.idempotency_key LIKE 'perfseed-%'
 *   - waitlist_entries.notes      = 'PERF_SEED'
 *
 * Run:    php artisan db:seed --class=PerfTestSeeder
 * Clean:  php artisan db:seed --class=PerfTestSeeder --  (set PERF_CLEAN=1)
 */
class PerfTestSeeder extends Seeder
{
    public function run(): void
    {
        $companyId = (int) env('PERF_COMPANY_ID', 13);

        if (env('PERF_CLEAN')) {
            $this->clean($companyId);
            return;
        }

        $empCount  = (int) env('PERF_EMP', 40);
        $custCount = (int) env('PERF_CUST', 5000);
        $apptCount = (int) env('PERF_APPT', 50000);
        $svcCount  = (int) env('PERF_SVC', 60);
        $catCount  = (int) env('PERF_CAT', 8);
        $waitCount = (int) env('PERF_WAIT', 300);

        $this->command->info("Seeding perf data → company {$companyId}");
        $t0 = microtime(true);

        // ── Branches: keep existing + add 2 so staff/services span a chain ──
        $branchIds = DB::table('branches')->where('company_id', $companyId)->pluck('id')->all();
        for ($i = 1; $i <= 2; $i++) {
            $branchIds[] = DB::table('branches')->insertGetId([
                'company_id'     => $companyId,
                'name_en'        => "PerfSeed Branch {$i}",
                'name_ar'        => "فرع تجريبي {$i}",
                'is_head_office' => false,
                'status'         => 'active',
                'slug'           => 'perfseed-branch-' . $companyId . '-' . $i . '-' . Str::lower(Str::random(5)),
                'sort_order'     => 10 + $i,
                'created_at'     => now(), 'updated_at' => now(),
            ]);
        }
        $this->command->info('  branches ready: ' . count($branchIds));

        // ── Service categories ──
        $catIds = [];
        for ($i = 1; $i <= $catCount; $i++) {
            $catIds[] = DB::table('service_categories')->insertGetId([
                'company_id' => $companyId,
                'slug'       => 'perfseed-cat-' . $companyId . '-' . $i,
                'sort_order' => $i,
                'name_en'    => "PerfSeed Category {$i}",
                'name_ar'    => "تصنيف تجريبي {$i}",
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // ── Services (spread across branches + categories) ──
        $serviceNames = ['Haircut', 'Beard Trim', 'Shave', 'Hair Color', 'Kids Cut', 'Styling', 'Facial', 'Head Massage'];
        $servicesByBranch = [];
        $svcRows = [];
        for ($i = 1; $i <= $svcCount; $i++) {
            $branchId = $branchIds[array_rand($branchIds)];
            $svcRows[] = [
                'branch_id'          => $branchId,
                'service_category_id'=> $catIds[array_rand($catIds)],
                'name_en'            => $serviceNames[$i % count($serviceNames)] . " #{$i}",
                'name_ar'            => 'خدمة تجريبية ' . $i,
                'description'        => 'PERF_SEED',
                'price'              => rand(20, 300),
                'currency'           => 'SAR',
                'duration_minutes'   => [15, 30, 45, 60][array_rand([0, 1, 2, 3])],
                'is_active'          => true,
                'is_bookable_online' => true,
                'service_type'       => 'single',
                'price_type'         => 'fixed',
                'sort_order'         => $i,
                'created_at'         => now(), 'updated_at' => now(),
            ];
        }
        DB::table('services')->insert($svcRows);
        foreach (DB::table('services')->where('description', 'PERF_SEED')->get(['id', 'branch_id', 'price', 'duration_minutes']) as $s) {
            $servicesByBranch[$s->branch_id][] = ['id' => $s->id, 'price' => (float) $s->price, 'dur' => (int) $s->duration_minutes];
        }
        $this->command->info('  services: ' . $svcCount);

        // ── Large employee photos (unoptimized, to expose the image problem) ──
        $imagePaths = $this->makeLargeImages(5);

        // ── Employees ──
        $pwd = Hash::make('password');
        $empRows = [];
        for ($i = 1; $i <= $empCount; $i++) {
            $branchId = $branchIds[array_rand($branchIds)];
            $empRows[] = [
                'company_id'  => $companyId,
                'branch_id'   => $branchId,
                'all_branches'=> false,
                'full_access' => false,
                'role_id'     => 4, // service_provider
                'name_en'     => 'PerfSeed Staff ' . $i,
                'name_ar'     => 'موظف تجريبي ' . $i,
                'phone'       => '05' . str_pad((string) rand(0, 99999999), 8, '0', STR_PAD_LEFT),
                'email'       => 'perfseed-emp-' . $companyId . '-' . $i . '@seed.test',
                'image'       => $imagePaths[$i % count($imagePaths)], // rotate the big photos
                'is_active'   => true,
                'is_bookable' => true,
                'national_id' => 'PERFSEED',
                'password'    => $pwd,
                'created_at'  => now(), 'updated_at' => now(),
            ];
        }
        DB::table('employees')->insert($empRows);
        $employeesByBranch = [];
        foreach (DB::table('employees')->where('national_id', 'PERFSEED')->where('company_id', $companyId)->get(['id', 'branch_id']) as $e) {
            $employeesByBranch[$e->branch_id][] = $e->id;
        }
        $this->command->info('  employees: ' . $empCount . ' (large photos assigned)');

        // ── Customers (global table, tagged via source) ──
        $custIds = [];
        $batch = [];
        for ($i = 1; $i <= $custCount; $i++) {
            $batch[] = [
                'name'       => 'PerfSeed Customer ' . $i,
                'phone'      => '9665' . str_pad((string) $i, 8, '0', STR_PAD_LEFT),
                'source'     => 'perf_seed',
                'created_at' => now(), 'updated_at' => now(),
            ];
            if (count($batch) >= 1000) {
                DB::table('customers')->insert($batch);
                $batch = [];
            }
        }
        if ($batch) {
            DB::table('customers')->insert($batch);
        }
        $customers = DB::table('customers')->where('source', 'perf_seed')
            ->get(['id', 'name', 'phone'])->all();
        $this->command->info('  customers: ' . count($customers));

        // ── Appointments (the heavy hitter) ──
        $pastStatuses   = ['completed', 'completed', 'completed', 'no_show', 'cancelled_by_customer', 'cancelled_by_salon', 'confirmed'];
        $futureStatuses = ['pending', 'pending', 'confirmed', 'confirmed', 'confirmed', 'draft'];
        $now = Carbon::now();
        $inserted = 0;
        $rows = [];

        for ($i = 1; $i <= $apptCount; $i++) {
            $branchId = $branchIds[array_rand($branchIds)];
            if (empty($servicesByBranch[$branchId]) || empty($employeesByBranch[$branchId])) {
                continue;
            }
            $svc  = $servicesByBranch[$branchId][array_rand($servicesByBranch[$branchId])];
            $emp  = $employeesByBranch[$branchId][array_rand($employeesByBranch[$branchId])];
            $cust = $customers[array_rand($customers)];

            // -365 .. +180 days, business hours, quarter-hour aligned
            $dayOffset = rand(-365, 180);
            $hour = rand(9, 20);
            $minute = [0, 15, 30, 45][array_rand([0, 1, 2, 3])];
            $start = $now->copy()->addDays($dayOffset)->setTime($hour, $minute, 0);
            $isPast = $start->lt($now);
            $status = $isPast
                ? $pastStatuses[array_rand($pastStatuses)]
                : $futureStatuses[array_rand($futureStatuses)];

            $rows[] = [
                'company_id'      => $companyId,
                'branch_id'       => $branchId,
                'customer_id'     => $cust->id,
                'customer_name'   => $cust->name,
                'customer_phone'  => $cust->phone,
                'employee_id'     => $emp,
                'service_id'      => $svc['id'],
                'start_time'      => $start,
                'end_time'        => $start->copy()->addMinutes($svc['dur']),
                'status'          => $status,
                'total_price'     => $svc['price'],
                'payment_status'  => $status === 'completed' ? 'paid' : 'pending',
                'reference'       => 'PS' . strtoupper(Str::random(8)),
                'booking_group_id'=> (string) Str::uuid(),
                'idempotency_key' => 'perfseed-' . Str::uuid(),
                'created_at'      => $start->copy()->subDays(rand(0, 5)),
                'updated_at'      => now(),
            ];

            if (count($rows) >= 2000) {
                DB::table('appointments')->insert($rows);
                $inserted += count($rows);
                $rows = [];
                if ($inserted % 10000 === 0) {
                    $this->command->info("  appointments: {$inserted}");
                }
            }
        }
        if ($rows) {
            DB::table('appointments')->insert($rows);
            $inserted += count($rows);
        }
        $this->command->info('  appointments: ' . $inserted . ' (total)');

        // ── Waitlist ──
        $waitRows = [];
        for ($i = 1; $i <= $waitCount; $i++) {
            $branchId = $branchIds[array_rand($branchIds)];
            if (empty($servicesByBranch[$branchId])) {
                continue;
            }
            $cust = $customers[array_rand($customers)];
            $waitRows[] = [
                'company_id'    => $companyId,
                'branch_id'     => $branchId,
                'customer_id'   => $cust->id,
                'customer_name' => $cust->name,
                'customer_phone'=> $cust->phone,
                'service_id'    => $servicesByBranch[$branchId][array_rand($servicesByBranch[$branchId])]['id'],
                'status'        => 'waiting',
                'priority'      => rand(0, 3),
                'notes'         => 'PERF_SEED',
                'created_at'    => now(), 'updated_at' => now(),
            ];
        }
        if ($waitRows) {
            DB::table('waitlist_entries')->insert($waitRows);
        }
        $this->command->info('  waitlist: ' . count($waitRows));

        $secs = round(microtime(true) - $t0, 1);
        $this->command->info("Done in {$secs}s. Clear cache: php artisan cache:clear");
    }

    /** Generate N large, unoptimized JPEGs under storage/app/public/employees. */
    private function makeLargeImages(int $count): array
    {
        $dir = storage_path('app/public/employees');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $paths = [];
        for ($n = 1; $n <= $count; $n++) {
            $file = $dir . '/perfseed-large-' . $n . '.jpg';
            $rel  = 'employees/perfseed-large-' . $n . '.jpg';
            $paths[] = $rel;

            if (is_file($file) && filesize($file) > 1_000_000) {
                continue; // already made
            }

            // 4000x3000 truecolor filled with noise → does not JPEG-compress
            // well, so it lands at several MB (a real "phone photo uploaded raw").
            $w = 4000; $h = 3000;
            $img = imagecreatetruecolor($w, $h);
            for ($y = 0; $y < $h; $y += 4) {
                for ($x = 0; $x < $w; $x += 4) {
                    $col = imagecolorallocate($img, rand(0, 255), rand(0, 255), rand(0, 255));
                    imagefilledrectangle($img, $x, $y, $x + 3, $y + 3, $col);
                }
            }
            imagejpeg($img, $file, 100);
            imagedestroy($img);
        }

        return $paths;
    }

    /** Remove everything this seeder created for the company. */
    private function clean(int $companyId): void
    {
        $this->command->warn("Cleaning perf data for company {$companyId}...");

        DB::table('appointments')->where('company_id', $companyId)
            ->where('idempotency_key', 'like', 'perfseed-%')->delete();
        DB::table('waitlist_entries')->where('company_id', $companyId)
            ->where('notes', 'PERF_SEED')->delete();
        DB::table('services')->where('description', 'PERF_SEED')
            ->whereIn('branch_id', DB::table('branches')->where('company_id', $companyId)->pluck('id'))->delete();
        DB::table('service_categories')->where('company_id', $companyId)
            ->where('slug', 'like', 'perfseed-%')->delete();
        DB::table('employees')->where('company_id', $companyId)
            ->where('national_id', 'PERFSEED')->delete();
        DB::table('branches')->where('company_id', $companyId)
            ->where('slug', 'like', 'perfseed-%')->delete();
        // Customers are global; only ours carry source=perf_seed.
        DB::table('customers')->where('source', 'perf_seed')->delete();

        foreach (glob(storage_path('app/public/employees/perfseed-large-*.jpg')) as $f) {
            @unlink($f);
        }

        $this->command->info('Perf data cleaned.');
    }
}
