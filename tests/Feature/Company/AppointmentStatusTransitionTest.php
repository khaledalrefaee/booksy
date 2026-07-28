<?php

namespace Tests\Feature\Company;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use App\Exceptions\IllegalStatusTransition;
use App\Models\Appointment;
use App\Models\AppointmentTransition;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Service;
use Tests\TestCase;

class AppointmentStatusTransitionTest extends TestCase
{
    private function makeAppointment(Company $company, AppointmentStatus $status): Appointment
    {
        $branch  = Branch::factory()->create(['company_id' => $company->id]);
        $service = Service::factory()->create(['branch_id' => $branch->id]);

        return Appointment::create([
            'company_id'  => $company->id,
            'branch_id'   => $branch->id,
            'service_id'  => $service->id,
            'start_time'  => now()->addHour(),
            'end_time'    => now()->addHours(2),
            'status'      => $status,
            'total_price' => 100,
        ]);
    }

    public function test_status_is_cast_to_the_enum(): void
    {
        $company     = Company::factory()->create();
        $appointment = $this->makeAppointment($company, AppointmentStatus::Pending);

        $this->assertInstanceOf(AppointmentStatus::class, $appointment->fresh()->status);
    }

    public function test_creating_an_appointment_opens_its_timeline(): void
    {
        $company     = Company::factory()->create();
        $appointment = $this->makeAppointment($company, AppointmentStatus::Pending);

        $opening = AppointmentTransition::where('appointment_id', $appointment->id)->first();

        $this->assertNotNull($opening, 'No opening transition was logged on creation.');
        $this->assertNull($opening->from_status);
        $this->assertSame(AppointmentStatus::Pending, $opening->to_status);
    }

    public function test_a_legal_transition_is_applied_and_logged(): void
    {
        $company     = Company::factory()->create();
        $appointment = $this->makeAppointment($company, AppointmentStatus::Pending);

        app(TransitionAppointment::class)(
            $appointment,
            AppointmentStatus::Confirmed,
            TransitionActor::Company,
        );

        $this->assertSame(AppointmentStatus::Confirmed, $appointment->fresh()->status);
        $this->assertSame('pending', $appointment->fresh()->status_previous);

        $this->assertDatabaseHas('appointment_transitions', [
            'appointment_id' => $appointment->id,
            'from_status'    => 'pending',
            'to_status'      => 'confirmed',
            'actor_type'     => 'company',
        ]);
    }

    public function test_an_illegal_transition_is_rejected_and_changes_nothing(): void
    {
        $company     = Company::factory()->create();
        $appointment = $this->makeAppointment($company, AppointmentStatus::Completed);

        $this->expectException(IllegalStatusTransition::class);

        try {
            app(TransitionAppointment::class)(
                $appointment,
                AppointmentStatus::Confirmed,
                TransitionActor::Company,
            );
        } finally {
            $this->assertSame(
                AppointmentStatus::Completed,
                $appointment->fresh()->status,
                'A rejected transition still altered the appointment.',
            );
        }
    }

    public function test_a_salon_cancellation_without_a_reason_is_refused(): void
    {
        $company     = Company::factory()->create();
        $appointment = $this->makeAppointment($company, AppointmentStatus::Confirmed);

        $this->expectException(IllegalStatusTransition::class);

        app(TransitionAppointment::class)(
            $appointment,
            AppointmentStatus::CancelledBySalon,
            TransitionActor::Company,
        );
    }

    public function test_a_salon_cancellation_with_a_reason_records_it(): void
    {
        $company     = Company::factory()->create();
        $appointment = $this->makeAppointment($company, AppointmentStatus::Confirmed);

        app(TransitionAppointment::class)(
            $appointment,
            AppointmentStatus::CancelledBySalon,
            TransitionActor::Company,
            ['reason' => 'Employee called in sick'],
        );

        $this->assertSame(AppointmentStatus::CancelledBySalon, $appointment->fresh()->status);
        $this->assertSame('Employee called in sick', $appointment->fresh()->rejection_reason);

        $this->assertDatabaseHas('appointment_transitions', [
            'appointment_id' => $appointment->id,
            'to_status'      => 'cancelled_by_salon',
            'reason'         => 'Employee called in sick',
        ]);
    }

    public function test_a_customer_cannot_cancel_as_the_salon(): void
    {
        $company     = Company::factory()->create();
        $appointment = $this->makeAppointment($company, AppointmentStatus::Confirmed);

        $this->expectException(IllegalStatusTransition::class);

        app(TransitionAppointment::class)(
            $appointment,
            AppointmentStatus::CancelledBySalon,
            TransitionActor::Customer,
            ['reason' => 'trying to blame the salon'],
        );
    }

    public function test_the_endpoint_refuses_an_illegal_move_with_422(): void
    {
        $company = Company::factory()->create();
        $this->actingAsCompany($company);

        $appointment = $this->makeAppointment($company, AppointmentStatus::Completed);

        $this->patchJson(
            route('company.appointments.update-status', $appointment),
            ['status' => AppointmentStatus::Pending->value],
        )->assertStatus(422)->assertJson(['ok' => false]);

        $this->assertSame(AppointmentStatus::Completed, $appointment->fresh()->status);
    }

    public function test_the_endpoint_returns_the_next_legal_moves(): void
    {
        $company = Company::factory()->create();
        $this->actingAsCompany($company);

        $appointment = $this->makeAppointment($company, AppointmentStatus::Pending);

        $response = $this->patchJson(
            route('company.appointments.update-status', $appointment),
            ['status' => AppointmentStatus::Confirmed->value],
        )->assertOk();

        $this->assertSame('confirmed', $response->json('status'));
        $this->assertContains('arrived', $response->json('allowed'));
    }
}
