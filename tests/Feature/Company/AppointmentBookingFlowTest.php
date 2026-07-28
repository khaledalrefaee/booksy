<?php

namespace Tests\Feature\Company;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Service;
use Tests\TestCase;

/**
 * End-to-end cover for the booking flow the appointments page drives:
 * create → save → read back on every view → change status.
 *
 * These go through the real HTTP routes, so they also prove the routes,
 * middleware and JSON contracts the page's JavaScript depends on.
 */
class AppointmentBookingFlowTest extends TestCase
{
    private Company $company;
    private Branch $branch;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->company = Company::factory()->create();
        $this->branch  = Branch::factory()->create(['company_id' => $this->company->id]);
        $this->service = Service::factory()->create([
            'branch_id'        => $this->branch->id,
            'duration_minutes' => 30,
            'price'            => 100,
            'is_active'        => true,
        ]);

        $this->actingAs($this->company, 'company');
    }

    private function slot(string $time = '11:00'): string
    {
        return now()->addDays(3)->format('Y-m-d') . " $time:00";
    }

    public function test_it_creates_an_appointment_from_the_quick_add_drawer(): void
    {
        $res = $this->postJson(route('company.appointments.quick-store'), [
            'branch_id'     => $this->branch->id,
            'start_time'    => $this->slot(),
            'service_ids'   => [$this->service->id],
            'customer_name' => 'Walk-in Wendy',
        ]);

        $res->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('appointments', [
            'id'         => $res->json('id'),
            'company_id' => $this->company->id,
            'status'     => 'confirmed',
        ]);
    }

    public function test_a_replayed_save_never_creates_a_duplicate(): void
    {
        $payload = [
            'idempotency_key' => 'flow-key-1',
            'branch_id'       => $this->branch->id,
            'start_time'      => $this->slot('12:00'),
            'service_ids'     => [$this->service->id],
            'customer_name'   => 'Replay Rita',
        ];

        $first  = $this->postJson(route('company.appointments.quick-store'), $payload)->assertOk();
        $second = $this->postJson(route('company.appointments.quick-store'), $payload)->assertOk();

        $this->assertSame($first->json('id'), $second->json('id'), 'replay returned a different appointment');
        $this->assertTrue($second->json('duplicate'));
        $this->assertSame(1, Appointment::where('customer_name', 'Replay Rita')->count());
    }

    public function test_a_saved_appointment_appears_on_every_view(): void
    {
        $id = $this->postJson(route('company.appointments.quick-store'), [
            'branch_id'     => $this->branch->id,
            'start_time'    => $this->slot('13:00'),
            'service_ids'   => [$this->service->id],
            'customer_name' => 'Visible Vera',
        ])->assertOk()->json('id');

        // calendar view
        $events = $this->getJson(route('company.appointments.calendar-events', [
            'start' => now()->addDays(2)->toDateString(),
            'end'   => now()->addDays(4)->toDateString(),
        ]))->assertOk()->json();
        $this->assertContains($id, array_column($events, 'id'), 'appointment missing from calendar-events');

        // staff view
        $staff = $this->getJson(route('company.appointments.staff-events', [
            'date' => now()->addDays(3)->toDateString(),
        ]))->assertOk()->json();
        $this->assertContains($id, array_column($staff['appointments'], 'id'), 'appointment missing from staff-events');

        // list view is served by the same calendar-events feed, already asserted above
    }

    public function test_status_can_be_updated(): void
    {
        $id = $this->postJson(route('company.appointments.quick-store'), [
            'branch_id'     => $this->branch->id,
            'start_time'    => $this->slot('14:00'),
            'service_ids'   => [$this->service->id],
            'customer_name' => 'Status Sam',
        ])->assertOk()->json('id');

        $this->patchJson(route('company.appointments.update-status', $id), ['status' => 'completed'])
            ->assertOk()
            ->assertJson(['ok' => true, 'status' => 'completed']);

        $this->assertDatabaseHas('appointments', ['id' => $id, 'status' => 'completed']);
    }

    public function test_double_booking_the_same_employee_is_refused(): void
    {
        $employee = Employee::factory()->create([
            'branch_id'   => $this->branch->id,
            'company_id'  => $this->company->id,
            'is_active'   => true,
            'is_bookable' => true,
        ]);

        $payload = fn (string $name) => [
            'branch_id'     => $this->branch->id,
            'start_time'    => $this->slot('15:00'),
            'service_ids'   => [$this->service->id],
            'employee_id'   => $employee->id,
            'customer_name' => $name,
        ];

        $this->postJson(route('company.appointments.quick-store'), $payload('First'))->assertOk();
        $this->postJson(route('company.appointments.quick-store'), $payload('Clash'))
            ->assertStatus(422)
            ->assertJson(['ok' => false, 'code' => 'employee_busy']);

        $this->assertSame(0, Appointment::where('customer_name', 'Clash')->count());
    }

    public function test_stats_endpoint_responds(): void
    {
        $this->getJson(route('company.appointments.stats', ['range' => 'today']))
            ->assertOk()
            ->assertJsonStructure(['ok', 'range', 'stats' => ['total', 'pending', 'ongoing', 'completed']]);
    }

    public function test_another_company_cannot_touch_this_appointment(): void
    {
        $id = $this->postJson(route('company.appointments.quick-store'), [
            'branch_id'     => $this->branch->id,
            'start_time'    => $this->slot('16:00'),
            'service_ids'   => [$this->service->id],
            'customer_name' => 'Private Pat',
        ])->assertOk()->json('id');

        $intruder = Company::factory()->create();
        $this->actingAs($intruder, 'company')
            ->patchJson(route('company.appointments.update-status', $id), ['status' => 'cancelled'])
            ->assertForbidden();

        $this->assertDatabaseHas('appointments', ['id' => $id, 'status' => 'confirmed']);
    }
}
