<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ResolvesOwnerCompany;
use App\Models\Appointment;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    use ResolvesOwnerCompany;

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'in:pending,confirmed,rejected,cancelled,completed,no_show'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
        ]);

        $query = Appointment::query()
            ->with(['branch.company', 'customer', 'employee', 'service.serviceCategory']);

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }

        $appointments = $query->orderByDesc('start_time')->paginate(15)->withQueryString();

        $companies = Company::query()->orderByLocalizedName()->get();

        return view('owner.appointments.index', [
            'appointments' => $appointments,
            'filterStatus' => $validated['status'] ?? null,
            'filterCompanyId' => isset($validated['company_id']) ? (int) $validated['company_id'] : null,
            'companies' => $companies,
        ]);
    }

    public function show(Appointment $appointment): View
    {
        $this->authorizeAppointment($appointment);

        $appointment->load(['branch.company', 'customer', 'employee', 'service.serviceCategory', 'handledBy', 'review']);

        return view('owner.appointments.show', [
            'appointment' => $appointment,
        ]);
    }
}
