<?php

namespace App\Http\Controllers\Owner\Concerns;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Service;
use App\Services\Owner\OwnerContext;

/**
 * Authorization helpers for the platform owner panel (not a single-company tenant).
 */
trait ResolvesOwnerCompany
{
    protected function ownerContext(): OwnerContext
    {
        return app(OwnerContext::class);
    }

    protected function authorizeBranch(Branch $branch): void
    {
        abort_unless($branch->exists, 404);
    }

    protected function authorizeService(Service $service): void
    {
        abort_unless($service->branch !== null, 404);
    }

    protected function authorizeEmployee(Employee $employee): void
    {
        abort_unless($employee->branch_id !== null && $employee->branch !== null, 404);
    }

    protected function authorizeAppointment(Appointment $appointment): void
    {
        abort_unless($appointment->exists, 404);
    }
}
