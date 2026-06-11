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
            'status'     => ['nullable', 'string', 'in:pending,confirmed,rejected,cancelled,completed,no_show'],
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'date_from'  => ['nullable', 'date'],
            'date_to'    => ['nullable', 'date'],
        ]);

        $q         = trim($request->input('q', ''));
        $sortField = in_array($request->input('sort'), ['start_time', 'created_at', 'total_price']) ? $request->input('sort') : 'start_time';
        $sortDir   = $request->input('dir') === 'asc' ? 'asc' : 'desc';

        $query = Appointment::query()
            ->with(['branch.company', 'customer', 'employee', 'service.serviceCategory']);

        if ($q !== '') {
            $query->whereHas('customer', function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%");
            });
        }

        if (! empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (! empty($validated['company_id'])) {
            $query->where('company_id', $validated['company_id']);
        }

        if (! empty($validated['date_from'])) {
            $query->whereDate('start_time', '>=', $validated['date_from']);
        }

        if (! empty($validated['date_to'])) {
            $query->whereDate('start_time', '<=', $validated['date_to']);
        }

        $appointments = $query->orderBy($sortField, $sortDir)->paginate(15)->withQueryString();

        $companies = Company::query()->orderByLocalizedName()->get();

        return view('owner.appointments.index', [
            'appointments'    => $appointments,
            'filterStatus'    => $validated['status'] ?? null,
            'filterCompanyId' => isset($validated['company_id']) ? (int) $validated['company_id'] : null,
            'filterDateFrom'  => $validated['date_from'] ?? '',
            'filterDateTo'    => $validated['date_to'] ?? '',
            'companies'       => $companies,
            'q'               => $q,
            'sortField'       => $sortField,
            'sortDir'         => $sortDir,
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
