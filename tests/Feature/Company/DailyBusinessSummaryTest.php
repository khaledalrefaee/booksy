<?php

namespace Tests\Feature\Company;

use App\Mail\DailyBusinessSummaryMail;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DailySummaryLog;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Services\DailyBusinessSummaryService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Cover for the Daily Business Summary email — the numbers behind it and the
 * scheduler command that ships it. Exercises the ten scenarios in the spec:
 * single/multi branch, empty day, pending-only, the full status mix, pending
 * exclusion, company-local timezone, company isolation, and once-per-day sends.
 */
class DailyBusinessSummaryTest extends TestCase
{
    private DailyBusinessSummaryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DailyBusinessSummaryService::class);

        // Freeze "now" at 20:00 company-local so the day window [00:00, now] is
        // stable and every "today" appointment below falls inside it.
        Carbon::setTestNow(Carbon::create(2026, 9, 22, 20, 0, 0, config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    // ── helpers ──────────────────────────────────────────────────────────────

    private function activeCompany(array $attrs = []): Company
    {
        return Company::factory()->create(array_merge([
            'status'            => 'active',
            'phone_verified_at' => now(), // confirmed account
        ], $attrs));
    }

    /** Create one appointment with full control and no incidental factory rows. */
    private function appt(Branch $branch, string $status, Carbon $start, array $attrs = []): Appointment
    {
        return Appointment::factory()->create(array_merge([
            'company_id'  => $branch->company_id,
            'branch_id'   => $branch->id,
            'customer_id' => null,
            'employee_id' => null,
            'service_id'  => null,
            'start_time'  => $start,
            'end_time'    => (clone $start)->addMinutes(30),
            'status'      => $status,
            'total_price' => 100,
        ], $attrs));
    }

    private function today(int $hour = 10): Carbon
    {
        return Carbon::now(config('app.timezone'))->setTime($hour, 0);
    }

    // ── 1. Single branch, full status mix, pending excluded ───────────────────

    public function test_single_branch_summary_counts_only_real_bookings(): void
    {
        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);

        // Real (confirmed → completed) = counted.
        $this->appt($branch, 'confirmed', $this->today(9));
        $this->appt($branch, 'completed', $this->today(11), ['total_price' => 250]);
        $this->appt($branch, 'completed', $this->today(12), ['total_price' => 150]);
        // Cancelled — reported separately, not "real".
        $this->appt($branch, 'cancelled_by_customer', $this->today(13));
        $this->appt($branch, 'cancelled_by_salon', $this->today(14));
        // Must never count toward statistics or revenue.
        $this->appt($branch, 'pending', $this->today(15), ['total_price' => 9999]);
        $this->appt($branch, 'draft', $this->today(16), ['total_price' => 9999]);
        $this->appt($branch, 'no_show', $this->today(17));

        $r = $this->service->build($company);
        $s = $r['summary'];

        $this->assertSame(3, $s['total'], 'real bookings = 1 confirmed + 2 completed');
        $this->assertSame(2, $s['completed']);
        $this->assertSame(2, $s['cancelled']);
        $this->assertSame(400.0, $s['revenue'], 'revenue = completed total_price only');
        $this->assertFalse($r['has_multiple_branches']);
    }

    // ── 2 & 3. Company-empty day is not reportable; established company is ─────

    public function test_company_with_no_data_at_all_is_not_reportable(): void
    {
        $company = $this->activeCompany();
        Branch::factory()->create(['company_id' => $company->id]);

        $r = $this->service->build($company);

        $this->assertSame(0, $r['summary']['total']);
        $this->assertFalse($this->service->hasReportableData($r));
    }

    public function test_zero_today_but_upcoming_bookings_is_reportable(): void
    {
        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->appt($branch, 'confirmed', $this->today(23)->addDays(2)); // future

        $r = $this->service->build($company);

        $this->assertSame(0, $r['summary']['total']);
        $this->assertSame(1, $r['upcoming']['count']);
        $this->assertTrue($this->service->hasReportableData($r));
    }

    // ── 4. Pending-only company: zeros, and empty ⇒ not reportable ────────────

    public function test_pending_only_company_reports_zeros(): void
    {
        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->appt($branch, 'pending', $this->today(10));
        $this->appt($branch, 'pending', $this->today(12));

        $r = $this->service->build($company);

        $this->assertSame(0, $r['summary']['total']);
        $this->assertSame(0.0, $r['summary']['revenue']);
        $this->assertFalse($this->service->hasReportableData($r));
    }

    // ── 5. Multiple branches: per-branch rows sum to the company total ────────

    public function test_multiple_branches_breakdown_sums_to_company_total(): void
    {
        $company = $this->activeCompany();
        $b1 = Branch::factory()->create(['company_id' => $company->id]);
        $b2 = Branch::factory()->create(['company_id' => $company->id]);

        $this->appt($b1, 'completed', $this->today(9), ['total_price' => 100]);
        $this->appt($b1, 'confirmed', $this->today(10));
        $this->appt($b2, 'completed', $this->today(11), ['total_price' => 300]);
        $this->appt($b2, 'cancelled_by_salon', $this->today(12));

        $r = $this->service->build($company);

        $this->assertTrue($r['has_multiple_branches']);
        $this->assertCount(2, $r['branches']);
        $this->assertSame(3, $r['summary']['total']);
        $this->assertSame(400.0, $r['summary']['revenue']);
        $this->assertSame(1, $r['summary']['cancelled']);

        $this->assertSame(
            $r['summary']['total'],
            (int) collect($r['branches'])->sum('total'),
            'branch rows must sum to the company total'
        );
        $this->assertSame(
            $r['summary']['revenue'],
            round((float) collect($r['branches'])->sum('revenue'), 2)
        );
    }

    // ── 6. Company isolation — never mix companies or foreign branches ────────

    public function test_report_only_includes_the_companys_own_data(): void
    {
        $companyA = $this->activeCompany();
        $branchA  = Branch::factory()->create(['company_id' => $companyA->id]);
        $this->appt($branchA, 'completed', $this->today(9), ['total_price' => 100]);

        $companyB = $this->activeCompany();
        $branchB  = Branch::factory()->create(['company_id' => $companyB->id]);
        $this->appt($branchB, 'completed', $this->today(10), ['total_price' => 5000]);
        $this->appt($branchB, 'confirmed', $this->today(11));

        $r = $this->service->build($companyA);

        $this->assertSame(1, $r['summary']['total']);
        $this->assertSame(100.0, $r['summary']['revenue']);
        $this->assertCount(1, $r['branches']);
    }

    // ── 7. New customers = first real appointment with this company today ─────

    public function test_new_customers_counts_only_first_time_customers(): void
    {
        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);

        $newCustomer      = Customer::factory()->create();
        $returningCustomer = Customer::factory()->create();

        // Returning customer already had a booking before today.
        $this->appt($branch, 'completed', $this->today(9)->subDays(3), ['customer_id' => $returningCustomer->id]);

        // Both have a booking today.
        $this->appt($branch, 'confirmed', $this->today(10), ['customer_id' => $newCustomer->id]);
        $this->appt($branch, 'confirmed', $this->today(11), ['customer_id' => $returningCustomer->id]);

        $r = $this->service->build($company);

        $this->assertSame(1, $r['summary']['new_customers']);
    }

    // ── Top services ──────────────────────────────────────────────────────────

    public function test_top_services_returns_the_three_most_booked_today(): void
    {
        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $cat     = ServiceCategory::factory()->create();

        $mk = fn (string $name) => Service::factory()->create([
            'branch_id' => $branch->id, 'service_category_id' => $cat->id, 'name_en' => $name, 'name_ar' => $name,
        ]);
        $haircut = $mk('Haircut');
        $color   = $mk('Color');
        $shave   = $mk('Shave');
        $facial  = $mk('Facial');

        foreach (range(1, 3) as $_) $this->appt($branch, 'completed', $this->today(9), ['service_id' => $haircut->id]);
        foreach (range(1, 2) as $_) $this->appt($branch, 'confirmed', $this->today(10), ['service_id' => $color->id]);
        $this->appt($branch, 'confirmed', $this->today(11), ['service_id' => $shave->id]);
        $this->appt($branch, 'pending', $this->today(12), ['service_id' => $facial->id]); // excluded

        $r = $this->service->build($company);

        $this->assertCount(3, $r['top_services']);
        $this->assertSame('Haircut', $r['top_services'][0]['name']);
        $this->assertSame(3, $r['top_services'][0]['count']);
        $this->assertSame('Color', $r['top_services'][1]['name']);
    }

    // ── Upcoming ────────────────────────────────────────────────────────────

    public function test_upcoming_counts_future_real_bookings_only(): void
    {
        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);

        $this->appt($branch, 'confirmed', $this->today(23)->addDays(1));
        $this->appt($branch, 'confirmed', $this->today(9)->addDays(3));
        $this->appt($branch, 'pending', $this->today(9)->addDays(2));    // excluded
        $this->appt($branch, 'completed', $this->today(9)->subDay());    // past

        $r = $this->service->build($company);

        $this->assertSame(2, $r['upcoming']['count']);
        $this->assertNotNull($r['upcoming']['next_at']);
    }

    // ── Comparison hidden without a baseline, shown with one ──────────────────

    public function test_comparison_is_hidden_without_history(): void
    {
        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->appt($branch, 'confirmed', $this->today(10));

        $r = $this->service->build($company);
        $this->assertNull($r['comparison']);
    }

    public function test_comparison_is_present_with_history(): void
    {
        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);

        // 7 bookings across the previous 7 days ⇒ avg 1/day.
        foreach (range(1, 7) as $d) {
            $this->appt($branch, 'confirmed', $this->today(10)->subDays($d));
        }
        // 3 today ⇒ +200% vs the 1/day average.
        foreach (range(1, 3) as $_) $this->appt($branch, 'confirmed', $this->today(10));

        $r = $this->service->build($company);

        $this->assertNotNull($r['comparison']);
        $this->assertSame(200, $r['comparison']['diff_pct']);
        $this->assertSame('up', $r['comparison']['direction']);
    }

    // ── 8. Timezone — day boundary follows the company timezone ───────────────

    public function test_day_window_uses_company_timezone(): void
    {
        $company = $this->activeCompany();

        // The company's timezone is the single source of truth for the window.
        $this->assertSame(config('app.timezone'), $company->timezone());

        $r = $this->service->build($company);

        $this->assertSame(config('app.timezone'), $r['tz']);
        $this->assertSame(
            Carbon::now($company->timezone())->startOfDay()->toDateString(),
            $r['date']->toDateString()
        );
    }

    // ── 9 & 10. Command: sends, gates on hour, never twice a day ─────────────

    public function test_command_sends_at_the_local_send_hour_and_logs_it(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::create(2026, 9, 22, 21, 0, 0, config('app.timezone')));

        $company = $this->activeCompany(['email' => 'owner@salon.test']);
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->appt($branch, 'completed', $this->today(10), ['total_price' => 500]);

        $this->artisan('summary:daily-business')->assertSuccessful();

        Mail::assertQueued(DailyBusinessSummaryMail::class, fn ($m) => $m->hasTo('owner@salon.test'));
        $this->assertDatabaseHas('daily_summary_logs', [
            'company_id'   => $company->id,
            'summary_date' => Carbon::now(config('app.timezone'))->toDateString(),
        ]);
    }

    public function test_command_does_not_send_outside_the_send_hour(): void
    {
        Mail::fake();
        Carbon::setTestNow(Carbon::create(2026, 9, 22, 10, 0, 0, config('app.timezone')));

        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->appt($branch, 'completed', $this->today(9), ['total_price' => 500]);

        $this->artisan('summary:daily-business')->assertSuccessful();

        Mail::assertNothingQueued();
    }

    public function test_command_never_sends_twice_in_the_same_day(): void
    {
        Mail::fake();

        $company = $this->activeCompany();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->appt($branch, 'completed', $this->today(10), ['total_price' => 500]);

        // --force bypasses the hour gate but not the once-a-day guard.
        $this->artisan('summary:daily-business', ['--force' => true])->assertSuccessful();
        $this->artisan('summary:daily-business', ['--force' => true])->assertSuccessful();

        Mail::assertQueued(DailyBusinessSummaryMail::class, 1);
        $this->assertSame(1, DailySummaryLog::where('company_id', $company->id)->count());
    }

    public function test_command_skips_inactive_but_still_mails_active_empty_companies(): void
    {
        Mail::fake();

        // Inactive company: never mailed, even with data.
        $inactive = Company::factory()->create(['status' => 'inactive']);
        $branchI  = Branch::factory()->create(['company_id' => $inactive->id]);
        $this->appt($branchI, 'completed', $this->today(10));

        // Active company with no bookings at all: still gets a zeroed summary.
        $emptyActive = $this->activeCompany(['email' => 'quiet@salon.test']);
        Branch::factory()->create(['company_id' => $emptyActive->id]);

        $this->artisan('summary:daily-business', ['--force' => true])->assertSuccessful();

        Mail::assertQueued(DailyBusinessSummaryMail::class, 1);
        Mail::assertQueued(DailyBusinessSummaryMail::class, fn ($m) => $m->hasTo('quiet@salon.test'));
    }

    public function test_command_skips_active_but_unconfirmed_companies(): void
    {
        Mail::fake();

        // Active but never confirmed the account (phone_verified_at is null).
        $unconfirmed = Company::factory()->create([
            'status' => 'active', 'phone_verified_at' => null,
        ]);
        $branch = Branch::factory()->create(['company_id' => $unconfirmed->id]);
        $this->appt($branch, 'completed', $this->today(10), ['total_price' => 500]);

        $this->artisan('summary:daily-business', ['--force' => true])->assertSuccessful();

        Mail::assertNothingQueued();
    }
}
