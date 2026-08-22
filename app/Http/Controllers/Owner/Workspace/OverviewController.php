<?php

namespace App\Http\Controllers\Owner\Workspace;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ScopesCompany;
use App\Models\Appointment;
use App\Models\Company;
use Illuminate\Support\Carbon;

/**
 * Company Workspace — Overview tab (REFERENCE IMPLEMENTATION).
 *
 * This is the canonical example every other tab agent should mirror:
 *  - controller lives in Owner\Workspace, uses the ScopesCompany trait
 *  - all data is scoped by $company->id (reusing tenant-agnostic models)
 *  - index() returns $this->tab('overview', $company, [...]) — a layout-less
 *    partial built entirely from the shared bk- component classes.
 */
class OverviewController extends Controller
{
    use ScopesCompany;

    public function index(Company $company)
    {
        $tz    = config('app.timezone');
        $today = Carbon::now($tz)->startOfDay();

        $apptBase = Appointment::query()->where('company_id', $company->id);

        $kpis = [
            'appointments_today' => (clone $apptBase)
                ->whereBetween('start_time', [$today, (clone $today)->endOfDay()])->count(),
            'appointments_upcoming' => (clone $apptBase)
                ->where('start_time', '>=', now())->count(),
            'appointments_pending' => (clone $apptBase)
                ->where('status', 'pending')->count(),
            'appointments_total' => (clone $apptBase)->count(),
        ];

        $recentAppointments = (clone $apptBase)
            ->with(['branch', 'customer', 'employee', 'service'])
            ->orderByDesc('start_time')
            ->limit(8)
            ->get();

        $branches = $company->branches()
            ->withCount(['employees', 'services'])
            ->orderBy('sort_order')
            ->get();

        return $this->tab('overview', $company, [
            'kpis'               => $kpis,
            'recentAppointments' => $recentAppointments,
            'branches'           => $branches,
        ]);
    }
}
