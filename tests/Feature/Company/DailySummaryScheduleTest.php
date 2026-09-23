<?php

namespace Tests\Feature\Company;

use App\Mail\DailyBusinessSummaryMail;
use App\Mail\HolidayGreetingMail;
use App\Models\Branch;
use App\Models\BranchWorkingHour;
use App\Models\Company;
use App\Models\CompanyHoliday;
use App\Services\DailySummaryScheduleResolver;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Cover for the send-time logic: one hour after closing on open days, a midday
 * greeting when closed (weekly day off or company holiday), and a 21:00 stats
 * fallback when no working hours are configured.
 */
class DailySummaryScheduleTest extends TestCase
{
    private DailySummaryScheduleResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->resolver = app(DailySummaryScheduleResolver::class);
        Carbon::setTestNow(Carbon::create(2026, 9, 22, 12, 0, 0, config('app.timezone')));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function tz(): string
    {
        return config('app.timezone');
    }

    private function todayDow(): int
    {
        return (int) Carbon::now($this->tz())->dayOfWeek;
    }

    private function hours(Branch $branch, int $dow, bool $isOpen, ?string $close = '20:00'): void
    {
        BranchWorkingHour::factory()->create([
            'branch_id'   => $branch->id,
            'day_of_week' => $dow,
            'is_open'     => $isOpen,
            'open_time'   => '09:00',
            'close_time'  => $isOpen ? $close : null,
            'shift_number' => 1,
        ]);
    }

    // ── No hours configured → stats at 21:00 ──────────────────────────────────

    public function test_no_working_hours_falls_back_to_9pm_stats(): void
    {
        $company = Company::factory()->create();
        Branch::factory()->create(['company_id' => $company->id]);

        $plan = $this->resolver->resolve($company, Carbon::now($this->tz()));

        $this->assertSame(DailySummaryScheduleResolver::MODE_STATS, $plan['mode']);
        $this->assertSame(21, $plan['hour']);
    }

    // ── Open today → one hour after the latest closing time ───────────────────

    public function test_open_day_sends_stats_one_hour_after_closing(): void
    {
        $company = Company::factory()->create();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->hours($branch, $this->todayDow(), true, '20:00');

        $plan = $this->resolver->resolve($company, Carbon::now($this->tz()));

        $this->assertSame(DailySummaryScheduleResolver::MODE_STATS, $plan['mode']);
        $this->assertSame(21, $plan['hour']);
    }

    public function test_closing_after_11pm_is_capped_at_23(): void
    {
        $company = Company::factory()->create();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->hours($branch, $this->todayDow(), true, '23:30');

        $plan = $this->resolver->resolve($company, Carbon::now($this->tz()));

        $this->assertSame(23, $plan['hour']);
    }

    public function test_latest_close_across_branches_wins(): void
    {
        $company = Company::factory()->create();
        $b1 = Branch::factory()->create(['company_id' => $company->id]);
        $b2 = Branch::factory()->create(['company_id' => $company->id]);
        $this->hours($b1, $this->todayDow(), true, '18:00');
        $this->hours($b2, $this->todayDow(), true, '22:00');

        $plan = $this->resolver->resolve($company, Carbon::now($this->tz()));

        $this->assertSame(23, $plan['hour']); // 22:00 + 1
    }

    // ── Weekly day off → midday greeting ──────────────────────────────────────

    public function test_configured_but_closed_today_sends_midday_greeting(): void
    {
        $company = Company::factory()->create();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        // Configured (a different day is open), but nothing open today.
        $this->hours($branch, ($this->todayDow() + 1) % 7, true, '20:00');

        $plan = $this->resolver->resolve($company, Carbon::now($this->tz()));

        $this->assertSame(DailySummaryScheduleResolver::MODE_GREETING, $plan['mode']);
        $this->assertSame(12, $plan['hour']);
        $this->assertNull($plan['holiday_name']);
    }

    // ── Company holiday → midday greeting with its name ───────────────────────

    public function test_company_holiday_sends_greeting_with_name(): void
    {
        $company = Company::factory()->create();
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->hours($branch, $this->todayDow(), true, '20:00'); // open, but holiday wins

        CompanyHoliday::create([
            'company_id' => $company->id,
            'name'       => 'National Day',
            'start_date' => Carbon::now($this->tz())->toDateString(),
            'end_date'   => Carbon::now($this->tz())->toDateString(),
        ]);

        $plan = $this->resolver->resolve($company, Carbon::now($this->tz()));

        $this->assertSame(DailySummaryScheduleResolver::MODE_GREETING, $plan['mode']);
        $this->assertSame(12, $plan['hour']);
        $this->assertSame('National Day', $plan['holiday_name']);
    }

    // ── Command wiring: greeting vs stats reach the queue at the right hour ────

    public function test_command_queues_a_greeting_on_a_holiday_at_midday(): void
    {
        Mail::fake();

        $company = Company::factory()->create(['status' => 'active', 'email' => 'owner@salon.test']);
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->hours($branch, $this->todayDow(), true, '20:00');
        CompanyHoliday::create([
            'company_id' => $company->id, 'name' => 'Eid',
            'start_date' => Carbon::now($this->tz())->toDateString(),
            'end_date'   => Carbon::now($this->tz())->toDateString(),
        ]);

        // setUp froze the clock at 12:00 — the greeting hour — so no --force.
        $this->artisan('summary:daily-business')->assertSuccessful();

        Mail::assertQueued(HolidayGreetingMail::class, fn ($m) => $m->hasTo('owner@salon.test'));
        Mail::assertNotQueued(DailyBusinessSummaryMail::class);
    }

    public function test_command_queues_stats_one_hour_after_close(): void
    {
        Mail::fake();
        // Close 18:00 → send hour 19:00.
        Carbon::setTestNow(Carbon::create(2026, 9, 22, 19, 0, 0, config('app.timezone')));

        $company = Company::factory()->create(['status' => 'active', 'email' => 'owner@salon.test']);
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $this->hours($branch, $this->todayDow(), true, '18:00');

        $this->artisan('summary:daily-business')->assertSuccessful();

        Mail::assertQueued(DailyBusinessSummaryMail::class, fn ($m) => $m->hasTo('owner@salon.test'));
        Mail::assertNotQueued(HolidayGreetingMail::class);
    }
}
