<?php

namespace Tests\Feature\Company;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BookingPolicy;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeWorkingHour;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Branch Settings (per-branch timezone, clock style, slot interval, booking
 * window) + the customer rules in the Booking & Cancellation Policy (online
 * booking, same-day, cancel / reschedule deadline) — and the booking engine
 * obeying both.
 *
 * The clock is frozen at 09:00 UTC = 12:00 Damascus = 13:00 Dubai.
 */
class BranchSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // 09:00 UTC, expressed in the app timezone: Carbon 3 uses the frozen
        // instant's zone as the default for parse(), so it must match production.
        Carbon::setTestNow(Carbon::parse('2026-09-23 09:00:00', 'UTC')->setTimezone(config('app.timezone')));
        app()->setLocale('en');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * A bookable branch: one employee working 09:00–17:00 every day + one service.
     * Keys that belong to the Booking & Cancellation Policy (allow_*, deadline)
     * are saved as the company-wide policy; the rest are branch settings.
     */
    private function bookableBranch(Company $company, array $settings = [], int $duration = 30): array
    {
        $policyKeys = array_flip((new BookingPolicy)->getFillable());
        $policy     = array_intersect_key($settings, $policyKeys);
        if ($policy) {
            $this->policy($company, $policy);
        }

        $branch   = Branch::factory()->create(['company_id' => $company->id] + array_diff_key($settings, $policyKeys));
        $employee = Employee::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        foreach (range(0, 6) as $dow) {
            EmployeeWorkingHour::create([
                'employee_id' => $employee->id, 'day_of_week' => $dow,
                'is_working'  => true, 'start_time' => '09:00', 'end_time' => '17:00',
            ]);
        }
        $service = Service::factory()->create(['branch_id' => $branch->id, 'duration_minutes' => $duration, 'is_active' => true]);

        return [$branch->fresh(), $employee, $service];
    }

    /** Save a policy row (company-wide when $branch is null). */
    private function policy(Company $company, array $values, ?Branch $branch = null): BookingPolicy
    {
        return $company->bookingPolicies()->updateOrCreate(
            ['branch_id' => $branch?->id],
            $values + BookingPolicy::defaults(),
        );
    }

    /** The Customer factory picks tag/source metadata arrays, not keys — pass keys. */
    private function customer(): Customer
    {
        return Customer::factory()->create(['tag' => 'regular', 'source' => 'website']);
    }

    private function slots(Employee $employee, Service $service, string $date): array
    {
        return $this->getJson(route('booking.slots', [
            'employee_id' => $employee->id, 'service_id' => $service->id, 'date' => $date,
        ]))->assertOk()->json();
    }

    private function validSettings(array $overrides = []): array
    {
        return $overrides + [
            'timezone'               => 'Asia/Dubai',
            'time_format'            => '24h',
            'appointment_interval'   => 30,
            'min_booking_notice'     => 60,
            'max_booking_days'       => 30,
            'first_day_of_week'      => 6,
        ];
    }

    // ── Settings page ────────────────────────────────────────────────────────

    public function test_new_branches_keep_the_previous_platform_behaviour(): void
    {
        $branch = Branch::factory()->create()->fresh();

        $this->assertSame('Asia/Damascus', $branch->timezone);
        $this->assertSame('12h', $branch->time_format);
        $this->assertSame(15, $branch->appointment_interval);
        $this->assertSame(0, $branch->min_booking_notice);
        $this->assertSame(0, $branch->first_day_of_week);

        // Customer rules come from the (default) policy: nothing restricted,
        // and the cancellation deadline starts at "up to the appointment time".
        $policy = $branch->bookingPolicy();
        $this->assertTrue($policy->allow_online_booking);
        $this->assertTrue($policy->allow_same_day_booking);
        $this->assertTrue($policy->allow_customer_cancel);
        $this->assertTrue($policy->allow_customer_reschedule);
        $this->assertSame(0, $policy->cancellation_deadline_minutes);
    }

    public function test_the_settings_page_renders_for_the_owner(): void
    {
        $company = $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));
        $branch  = Branch::factory()->create(['company_id' => $company->id, 'timezone' => 'Asia/Dubai']);

        $this->get(route('company.branches.settings.edit', $branch))
            ->assertOk()
            ->assertSee('Asia/Dubai')
            ->assertSee('UTC+04:00')
            ->assertSee('name="appointment_interval"', false);
    }

    public function test_another_companys_branch_is_forbidden(): void
    {
        $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));
        $foreign = Branch::factory()->create();

        $this->get(route('company.branches.settings.edit', $foreign))->assertForbidden();
        $this->put(route('company.branches.settings.update', $foreign), $this->validSettings())->assertForbidden();
    }

    public function test_settings_are_saved_per_branch(): void
    {
        $company = $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $sibling = Branch::factory()->create(['company_id' => $company->id]);

        $this->put(route('company.branches.settings.update', $branch), $this->validSettings())
            ->assertRedirect(route('company.branches.settings.edit', $branch))
            ->assertSessionHas('success');

        $branch->refresh();
        $this->assertSame('Asia/Dubai', $branch->timezone);
        $this->assertSame('24h', $branch->time_format);
        $this->assertSame(30, $branch->appointment_interval);
        $this->assertSame(6, $branch->first_day_of_week);

        // The sibling branch is untouched.
        $this->assertSame('Asia/Damascus', $sibling->fresh()->timezone);
        $this->assertSame(15, $sibling->fresh()->appointment_interval);
    }

    // ── Company appointments calendar ────────────────────────────────────────

    public function test_the_appointments_calendar_receives_each_branch_clock(): void
    {
        $company  = $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));
        $damascus = Branch::factory()->create(['company_id' => $company->id, 'timezone' => 'Asia/Damascus', 'time_format' => '24h', 'appointment_interval' => 30, 'first_day_of_week' => 6]);
        $dubai    = Branch::factory()->create(['company_id' => $company->id, 'timezone' => 'Asia/Dubai',    'time_format' => '12h', 'appointment_interval' => 15, 'first_day_of_week' => 0]);

        $html = $this->get(route('company.appointments.index'))->assertOk()->getContent();

        preg_match('/window\.BK = (\{.*?\});<\/script>/s', $html, $m);
        $bk = json_decode($m[1] ?? 'null', true);
        $this->assertNotNull($bk, 'calendar data bridge missing');

        $this->assertSame(['tz' => 'Asia/Damascus', 'time_format' => '24h', 'first_day' => 6, 'interval' => 30],
            array_intersect_key($bk['branchSettings'][$damascus->id], array_flip(['tz', 'time_format', 'first_day', 'interval'])));
        $this->assertSame(['tz' => 'Asia/Dubai', 'time_format' => '12h', 'first_day' => 0, 'interval' => 15],
            array_intersect_key($bk['branchSettings'][$dubai->id], array_flip(['tz', 'time_format', 'first_day', 'interval'])));
    }

    public function test_appointment_detail_labels_follow_the_branch_time_format(): void
    {
        $company = $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));
        [$b24, $e24, $s24] = $this->bookableBranch($company, ['time_format' => '24h']);
        [$b12, $e12, $s12] = $this->bookableBranch($company, ['time_format' => '12h']);

        $a24 = $this->customerAppointment($b24, $e24, $s24, '2026-09-25 14:30:00');
        $a12 = $this->customerAppointment($b12, $e12, $s12, '2026-09-25 14:30:00');

        $this->getJson(route('company.appointments.details-json', $a24))->assertOk()->assertJson(['startLabel' => '14:30', 'endLabel' => '15:00']);
        $this->getJson(route('company.appointments.details-json', $a12))->assertOk()->assertJson(['startLabel' => '2:30 PM', 'endLabel' => '3:00 PM']);
    }

    // ── Booking & Cancellation Policy page (customer rules) ──────────────────

    public function test_the_policy_page_saves_the_customer_rules(): void
    {
        $company = $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));
        $branch  = Branch::factory()->create(['company_id' => $company->id]);

        $this->get(route('company.booking-policy.edit'))
            ->assertOk()
            ->assertSee('name="unified[cancellation_deadline_minutes]"', false)
            ->assertSee('name="unified[allow_online_booking]"', false);

        // Unchecked switches are simply absent from the request → saved as off.
        $this->post(route('company.booking-policy.update'), [
            'mode'    => 'unified',
            'unified' => ['allow_customer_cancel' => '1', 'cancellation_deadline_minutes' => 120],
        ])->assertRedirect()->assertSessionHas('success');

        $policy = $branch->fresh()->bookingPolicy();
        $this->assertFalse($policy->allow_online_booking);
        $this->assertFalse($policy->allow_same_day_booking);
        $this->assertTrue($policy->allow_customer_cancel);
        $this->assertFalse($policy->allow_customer_reschedule);
        $this->assertSame(120, $policy->cancellation_deadline_minutes);
    }

    public function test_a_deadline_outside_the_offered_options_falls_back_to_none(): void
    {
        $company = $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));

        $this->post(route('company.booking-policy.update'), [
            'mode'    => 'unified',
            'unified' => ['cancellation_deadline_minutes' => 99999],
        ])->assertRedirect();

        $this->assertSame(0, $company->effectiveBookingPolicy()->cancellation_deadline_minutes);
    }

    public function test_per_branch_policies_apply_to_their_own_branch_only(): void
    {
        $company = Company::factory()->create(['booking_policy_mode' => 'per_branch']);
        [$damascus, $eDam, $sDam] = $this->bookableBranch($company);
        [$aleppo,   $eAlp, $sAlp] = $this->bookableBranch($company);

        $this->policy($company, [], null);                                   // company default: open
        $this->policy($company, ['allow_online_booking' => false], $damascus); // Damascus: closed online

        $this->assertSame('online_disabled', $this->slots($eDam, $sDam, '2026-09-25')['reason']);
        $this->assertNotEmpty($this->slots($eAlp, $sAlp, '2026-09-25')['slots']);
    }

    public function test_invalid_values_are_rejected_and_nothing_changes(): void
    {
        $company = $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));
        $branch  = Branch::factory()->create(['company_id' => $company->id]);

        $this->from(route('company.branches.settings.edit', $branch))
            ->put(route('company.branches.settings.update', $branch), $this->validSettings([
                'timezone'             => 'GMT+3',   // an offset, not an IANA zone
                'appointment_interval' => 7,
                'time_format'          => '36h',
            ]))
            ->assertRedirect(route('company.branches.settings.edit', $branch))
            ->assertSessionHasErrors(['timezone', 'appointment_interval', 'time_format']);

        $this->assertSame('Asia/Damascus', $branch->fresh()->timezone);
    }

    // ── Clock ────────────────────────────────────────────────────────────────

    public function test_each_branch_has_its_own_clock_and_format(): void
    {
        $damascus = Branch::factory()->create(['timezone' => 'Asia/Damascus', 'time_format' => '24h']);
        $dubai    = Branch::factory()->create(['timezone' => 'Asia/Dubai',    'time_format' => '12h']);
        $aleppo   = Branch::factory()->create(['timezone' => 'Asia/Damascus', 'time_format' => '12h']);

        $this->assertSame('12:00', $damascus->localNow()->format('H:i'));
        $this->assertSame('13:00', $dubai->localNow()->format('H:i'));
        $this->assertSame('12:00', $aleppo->localNow()->format('H:i'));

        $this->assertSame('UTC+03:00', $damascus->utcOffsetLabel());
        $this->assertSame('UTC+04:00', $dubai->utcOffsetLabel());

        // One stored instant, shown on each branch's clock (storage ≠ display).
        $instant = Carbon::parse('2026-09-23 15:00:00', 'UTC');
        $this->assertSame('18:00', $damascus->formatTime($damascus->toLocal($instant)));
        $this->assertSame('7:00 PM', $dubai->formatTime($dubai->toLocal($instant)));

        // Working-hour strings are formatted too.
        $this->assertSame('14:30', $damascus->formatTime('14:30:00'));
        $this->assertSame('2:30 PM', $aleppo->formatTime('14:30'));

        // Arabic interface: same clock, localized meridiem.
        app()->setLocale('ar');
        $this->assertSame('2:30 م', $aleppo->formatTime('14:30'));
        $this->assertSame('14:30', $damascus->formatTime('14:30'));
    }

    // ── Booking engine ───────────────────────────────────────────────────────

    public function test_the_interval_sets_start_times_but_not_the_service_length(): void
    {
        $company = Company::factory()->create();
        [, $employee, $service] = $this->bookableBranch($company, ['appointment_interval' => 30], 45);

        $slots = $this->slots($employee, $service, '2026-09-25')['slots'];

        $this->assertSame(['09:00', '09:30', '10:00', '10:30'], array_slice(array_column($slots, 'time'), 0, 4));
        $ten = collect($slots)->firstWhere('time', '10:00');
        $this->assertSame('2026-09-25 10:45:00', $ten['end']);   // 45-minute service, not 30
        $this->assertSame('16:00', end($slots)['time']);         // last start that still fits by 17:00
    }

    public function test_different_intervals_produce_different_grids(): void
    {
        $company = Company::factory()->create();
        [, $e15, $s15] = $this->bookableBranch($company, ['appointment_interval' => 15]);
        [, $e60, $s60] = $this->bookableBranch($company, ['appointment_interval' => 60]);

        $this->assertSame(['09:00', '09:15', '09:30'], array_slice(array_column($this->slots($e15, $s15, '2026-09-25')['slots'], 'time'), 0, 3));
        $this->assertSame(['09:00', '10:00', '11:00'], array_slice(array_column($this->slots($e60, $s60, '2026-09-25')['slots'], 'time'), 0, 3));
    }

    public function test_slot_labels_follow_the_branch_time_format(): void
    {
        $company = Company::factory()->create();
        [, $e24, $s24] = $this->bookableBranch($company, ['time_format' => '24h', 'appointment_interval' => 60]);
        [, $e12, $s12] = $this->bookableBranch($company, ['time_format' => '12h', 'appointment_interval' => 60]);

        $this->assertSame('14:00', collect($this->slots($e24, $s24, '2026-09-25')['slots'])->firstWhere('time', '14:00')['label']);
        $this->assertSame('2:00 PM', collect($this->slots($e12, $s12, '2026-09-25')['slots'])->firstWhere('time', '14:00')['label']);
    }

    public function test_today_is_cut_off_by_each_branch_own_clock(): void
    {
        $company = Company::factory()->create();
        [, $eDam, $sDam] = $this->bookableBranch($company, ['timezone' => 'Asia/Damascus', 'appointment_interval' => 60]);
        [, $eDxb, $sDxb] = $this->bookableBranch($company, ['timezone' => 'Asia/Dubai',    'appointment_interval' => 60]);

        // 12:00 in Damascus → first free start 13:00; 13:00 in Dubai → 14:00.
        $this->assertSame('13:00', $this->slots($eDam, $sDam, '2026-09-23')['slots'][0]['time']);
        $this->assertSame('14:00', $this->slots($eDxb, $sDxb, '2026-09-23')['slots'][0]['time']);
    }

    public function test_minimum_notice_hides_early_slots_and_blocks_booking(): void
    {
        $company = Company::factory()->create();
        [$branch, $employee, $service] = $this->bookableBranch($company, ['appointment_interval' => 30, 'min_booking_notice' => 120]);

        // 12:00 now + 2h notice → first start 14:00
        $this->assertSame('14:00', $this->slots($employee, $service, '2026-09-23')['slots'][0]['time']);

        $this->actingAsCustomer($this->customer())
            ->postJson(route('booking.book'), [
                'service_id' => $service->id, 'employee_id' => $employee->id, 'start_time' => '2026-09-23 13:00:00',
            ])
            ->assertStatus(422)->assertJson(['reason' => 'too_soon']);
    }

    public function test_same_day_booking_can_be_switched_off(): void
    {
        $company = Company::factory()->create();
        [, $employee, $service] = $this->bookableBranch($company, ['allow_same_day_booking' => false]);

        $today = $this->slots($employee, $service, '2026-09-23');
        $this->assertFalse($today['available']);
        $this->assertSame('same_day_disabled', $today['reason']);
        $this->assertNotEmpty($this->slots($employee, $service, '2026-09-24')['slots']);

        $this->actingAsCustomer($this->customer())
            ->postJson(route('booking.book'), [
                'service_id' => $service->id, 'employee_id' => $employee->id, 'start_time' => '2026-09-23 15:00:00',
            ])
            ->assertStatus(422)->assertJson(['reason' => 'same_day_disabled']);
    }

    public function test_maximum_advance_booking_is_enforced(): void
    {
        $company = Company::factory()->create();
        [, $employee, $service] = $this->bookableBranch($company, ['max_booking_days' => 7]);

        $this->assertNotEmpty($this->slots($employee, $service, '2026-09-30')['slots']);
        $this->assertSame('too_far', $this->slots($employee, $service, '2026-10-01')['reason']);
    }

    public function test_online_booking_can_be_switched_off(): void
    {
        $company = Company::factory()->create();
        [, $employee, $service] = $this->bookableBranch($company, ['allow_online_booking' => false]);

        $this->assertSame('online_disabled', $this->slots($employee, $service, '2026-09-25')['reason']);

        $this->actingAsCustomer($this->customer())
            ->postJson(route('booking.book'), [
                'service_id' => $service->id, 'employee_id' => $employee->id, 'start_time' => '2026-09-25 10:00:00',
            ])
            ->assertStatus(422)->assertJson(['reason' => 'online_disabled']);

        $this->assertSame(0, Appointment::count());
    }

    public function test_a_valid_booking_is_stored_as_the_branch_wall_clock_time(): void
    {
        $company = Company::factory()->create();
        [$branch, $employee, $service] = $this->bookableBranch($company, ['timezone' => 'Asia/Dubai', 'appointment_interval' => 30], 45);

        $this->actingAsCustomer($this->customer())
            ->postJson(route('booking.book'), [
                'service_id' => $service->id, 'employee_id' => $employee->id, 'start_time' => '2026-09-25 10:00:00',
            ])
            ->assertCreated();

        $appt = Appointment::sole();
        $this->assertSame('2026-09-25 10:00:00', $appt->start_time->toDateTimeString());
        $this->assertSame('2026-09-25 10:45:00', $appt->end_time->toDateTimeString());
        // …which is 06:00 UTC as a real instant.
        $this->assertSame('06:00', $branch->wallToInstant($appt->start_time)->utc()->format('H:i'));
    }

    // ── Empty days: why, and when to offer the waitlist ─────────────────────

    private function groupSlots(Branch $branch, Service $service, string $date): array
    {
        return $this->getJson(route('booking.group-slots', [
            'branch_id' => $branch->id, 'mode' => 'split', 'date' => $date,
            'guests' => [['service_ids' => [$service->id]]],
        ]))->assertOk()->json();
    }

    /** A branch whose only employee has NO own schedule; $open = branch hours per weekday. */
    private function branchWithHoursOnly(array $openDays, string $from = '09:00', string $to = '17:00'): array
    {
        $company  = Company::factory()->create();
        $branch   = Branch::factory()->create(['company_id' => $company->id, 'appointment_interval' => 60]);
        Employee::factory()->create(['company_id' => $company->id, 'branch_id' => $branch->id]);
        foreach ($openDays as $dow) {
            $branch->workingHours()->create(['day_of_week' => $dow, 'is_open' => true, 'open_time' => $from, 'close_time' => $to]);
        }
        $service = Service::factory()->create(['branch_id' => $branch->id, 'duration_minutes' => 60, 'is_active' => true]);

        return [$branch->fresh(), $service];
    }

    public function test_staff_without_their_own_schedule_work_the_branch_hours(): void
    {
        // 2026-09-25 is a Friday (5). Staff have no schedule → branch hours apply.
        [$branch, $service] = $this->branchWithHoursOnly([5], '10:00', '14:00');

        $d = $this->groupSlots($branch, $service, '2026-09-25');
        $this->assertSame(['10:00', '11:00', '12:00', '13:00'], array_column($d['slots'], 'time'));
        $this->assertSame([5], $branch->openWeekdays());
    }

    public function test_a_branch_with_no_hours_anywhere_says_so(): void
    {
        [$branch, $service] = $this->branchWithHoursOnly([]);

        $d = $this->groupSlots($branch, $service, '2026-09-25');
        $this->assertSame('no_hours', $d['reason']);
        $this->assertSame([], $branch->openWeekdays());
    }

    public function test_a_closed_day_is_not_fully_booked(): void
    {
        [$branch, $service] = $this->branchWithHoursOnly([5]); // open Fridays only

        $this->assertSame('closed', $this->groupSlots($branch, $service, '2026-09-26')['reason']); // Saturday
    }

    public function test_a_day_whose_times_have_passed_is_not_fully_booked(): void
    {
        // Wednesday (3) 09:00–11:00; the frozen clock says 12:00 → nothing left today.
        [$branch, $service] = $this->branchWithHoursOnly([3], '09:00', '11:00');

        $this->assertSame('no_more_today', $this->groupSlots($branch, $service, '2026-09-23')['reason']);
    }

    public function test_only_a_genuinely_full_day_is_fully_booked(): void
    {
        [$branch, $service] = $this->branchWithHoursOnly([5], '10:00', '11:00'); // one 60-min slot
        $employee = $branch->employees()->first();

        Appointment::create([
            'company_id' => $branch->company_id, 'branch_id' => $branch->id, 'employee_id' => $employee->id,
            'service_id' => $service->id, 'customer_id' => $this->customer()->id, 'reference' => Appointment::newReference(),
            'start_time' => '2026-09-25 10:00:00', 'end_time' => '2026-09-25 11:00:00',
            'status' => AppointmentStatus::Confirmed, 'total_price' => 100, 'payment_status' => 'pending',
        ]);

        $d = $this->groupSlots($branch, $service, '2026-09-25');
        $this->assertSame('fully_booked', $d['reason']);
        $this->assertSame([], $d['slots']);
    }

    // ── Approval vs automatic confirmation ───────────────────────────────────

    private function bookOnline(Customer $customer, Employee $employee, Service $service, string $start = '2026-09-25 10:00:00')
    {
        return $this->actingAsCustomer($customer)->postJson(route('booking.book'), [
            'service_id' => $service->id, 'employee_id' => $employee->id, 'start_time' => $start,
        ]);
    }

    /** $count past no-shows for this customer at the branch's company. */
    private function noShows(Branch $branch, Employee $employee, Service $service, Customer $customer, int $count, int $daysAgo = 10): void
    {
        foreach (range(1, $count) as $i) {
            Appointment::create([
                'company_id' => $branch->company_id, 'branch_id' => $branch->id,
                'employee_id' => $employee->id, 'service_id' => $service->id,
                'customer_id' => $customer->id, 'reference' => Appointment::newReference(),
                'start_time' => now()->subDays($daysAgo + $i)->setTime(10, 0), 'end_time' => now()->subDays($daysAgo + $i)->setTime(10, 30),
                'status' => AppointmentStatus::NoShow, 'total_price' => 100, 'payment_status' => 'pending',
            ]);
        }
    }

    public function test_online_bookings_wait_for_approval_by_default(): void
    {
        $company = Company::factory()->create();
        [, $employee, $service] = $this->bookableBranch($company);

        $this->bookOnline($this->customer(), $employee, $service)
            ->assertCreated()->assertJson(['booked' => true, 'confirmed' => false]);

        $this->assertSame(AppointmentStatus::Pending, Appointment::sole()->status);
    }

    public function test_online_bookings_can_be_confirmed_automatically(): void
    {
        $company = Company::factory()->create();
        [, $employee, $service] = $this->bookableBranch($company, ['auto_confirm_online_bookings' => true]);

        $this->bookOnline($this->customer(), $employee, $service)
            ->assertCreated()->assertJson(['booked' => true, 'confirmed' => true]);

        $this->assertSame(AppointmentStatus::Confirmed, Appointment::sole()->status);
    }

    public function test_repeat_no_shows_still_need_approval_when_protection_asks_for_it(): void
    {
        $company  = Company::factory()->create();
        [$branch, $employee, $service] = $this->bookableBranch($company, [
            'auto_confirm_online_bookings' => true,
            'protection_enabled' => true, 'action_manual_confirm' => true,
            'offense_threshold' => 2, 'offense_window_days' => 60,
        ]);

        $risky = $this->customer();
        $this->noShows($branch, $employee, $service, $risky, 2);
        $this->bookOnline($risky, $employee, $service)->assertCreated()->assertJson(['confirmed' => false]);

        // One no-show is under the threshold → confirmed.
        $once = $this->customer();
        $this->noShows($branch, $employee, $service, $once, 1);
        $this->bookOnline($once, $employee, $service, '2026-09-25 11:00:00')->assertCreated()->assertJson(['confirmed' => true]);

        // No-shows older than the window don't count.
        $old = $this->customer();
        $this->noShows($branch, $employee, $service, $old, 2, 90);
        $this->bookOnline($old, $employee, $service, '2026-09-25 12:00:00')->assertCreated()->assertJson(['confirmed' => true]);
    }

    public function test_repeat_no_shows_are_confirmed_when_protection_is_off(): void
    {
        $company = Company::factory()->create();
        [$branch, $employee, $service] = $this->bookableBranch($company, [
            'auto_confirm_online_bookings' => true, 'protection_enabled' => false,
        ]);

        $risky = $this->customer();
        $this->noShows($branch, $employee, $service, $risky, 3);

        $this->bookOnline($risky, $employee, $service)->assertCreated()->assertJson(['confirmed' => true]);
    }

    public function test_the_policy_page_saves_the_approval_choice(): void
    {
        $company = $this->actingAsCompany(Company::factory()->create(['phone_verified_at' => now()]));

        $this->get(route('company.booking-policy.edit'))
            ->assertOk()->assertSee('name="unified[auto_confirm_online_bookings]"', false);

        $this->post(route('company.booking-policy.update'), [
            'mode' => 'unified', 'unified' => ['auto_confirm_online_bookings' => '1'],
        ])->assertRedirect();
        $this->assertTrue($company->effectiveBookingPolicy()->auto_confirm_online_bookings);

        $this->post(route('company.booking-policy.update'), [
            'mode' => 'unified', 'unified' => ['auto_confirm_online_bookings' => '0'],
        ])->assertRedirect();
        $this->assertFalse($company->effectiveBookingPolicy()->auto_confirm_online_bookings);
    }

    // ── Customer self-service ────────────────────────────────────────────────

    private function customerAppointment(Branch $branch, Employee $employee, Service $service, string $start): Appointment
    {
        return Appointment::create([
            'company_id' => $branch->company_id, 'branch_id' => $branch->id,
            'employee_id' => $employee->id, 'service_id' => $service->id,
            'customer_id' => $this->customer()->id, 'reference' => Appointment::newReference(),
            'start_time' => $start, 'end_time' => Carbon::parse($start)->addMinutes(30),
            'status' => AppointmentStatus::Confirmed, 'total_price' => 100, 'payment_status' => 'pending',
        ]);
    }

    public function test_cancellation_respects_the_switch_and_the_deadline(): void
    {
        $company = Company::factory()->create();
        [$branch, $employee, $service] = $this->bookableBranch($company, ['cancellation_deadline_minutes' => 120]);

        // 13:30 is 90 minutes away → inside the 2-hour deadline
        $late = $this->customerAppointment($branch, $employee, $service, '2026-09-23 13:30:00');
        $this->actingAsCustomer($late->customer)
            ->post(route('account.appointment.cancel', $late))
            ->assertSessionHas('account_error');
        $this->assertSame(AppointmentStatus::Confirmed, $late->fresh()->status);

        // Tomorrow is fine
        $ok = $this->customerAppointment($branch, $employee, $service, '2026-09-24 13:30:00');
        $this->actingAsCustomer($ok->customer)
            ->post(route('account.appointment.cancel', $ok))
            ->assertSessionHas('account_success');
        $this->assertSame(AppointmentStatus::CancelledByCustomer, $ok->fresh()->status);

        // Switched off entirely
        $this->policy($company, ['allow_customer_cancel' => false]);
        $off = $this->customerAppointment($branch, $employee, $service, '2026-09-26 13:30:00');
        $this->actingAsCustomer($off->customer)
            ->post(route('account.appointment.cancel', $off))
            ->assertSessionHas('account_error');
        $this->assertSame(AppointmentStatus::Confirmed, $off->fresh()->status);
    }

    public function test_rescheduling_can_be_switched_off(): void
    {
        $company = Company::factory()->create();
        [$branch, $employee, $service] = $this->bookableBranch($company, ['allow_customer_reschedule' => false]);
        $appt = $this->customerAppointment($branch, $employee, $service, '2026-09-25 10:00:00');

        $this->actingAsCustomer($appt->customer)
            ->post(route('account.appointment.reschedule', $appt), ['start_time' => '2026-09-25 12:00:00'])
            ->assertSessionHas('account_error');

        $this->assertSame('2026-09-25 10:00:00', $appt->fresh()->start_time->toDateTimeString());
    }
}
