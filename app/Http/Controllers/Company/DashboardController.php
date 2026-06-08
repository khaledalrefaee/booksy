<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Owner\DashboardStatisticsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatisticsService $dashboardStatistics,
    ) {}

    public function index(): View
    {
        /** @var \App\Models\Company $company */
        $company = Auth::guard('company')->user();

        $stats = $this->dashboardStatistics->forCompany($company);
        $chartData = $this->dashboardStatistics->chartDataForCompany($company);

        $recentAppointments = Appointment::query()
            ->where('company_id', $company->id)
            ->with(['branch', 'customer', 'service', 'service.serviceCategory'])
            ->orderByDesc('start_time')
            ->limit(6)
            ->get();

        return view('company.index', compact('stats', 'recentAppointments', 'chartData', 'company'));
    }
}
