<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Owner\DashboardStatisticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        $now = now(config('app.timezone'));

        // Upcoming appointments (soonest first), then fill with recent past
        $upcoming = Appointment::query()
            ->where('company_id', $company->id)
            ->where('start_time', '>=', $now)
            ->with(['branch', 'customer', 'service', 'service.serviceCategory'])
            ->orderBy('start_time')
            ->limit(6)
            ->get();

        $remaining = 6 - $upcoming->count();

        $recentAppointments = $remaining > 0
            ? $upcoming->concat(
                Appointment::query()
                    ->where('company_id', $company->id)
                    ->where('start_time', '<', $now)
                    ->with(['branch', 'customer', 'service', 'service.serviceCategory'])
                    ->orderByDesc('start_time')
                    ->limit($remaining)
                    ->get()
            )
            : $upcoming;

        // Build month options: last 12 months for the month picker
        $monthOptions = [];
        for ($i = 0; $i < 12; $i++) {
            $m = now(config('app.timezone'))->subMonths($i);
            $monthOptions[] = [
                'value' => $m->format('Y-m'),
                'label' => $m->format('m/Y'),
            ];
        }

        return view('company.index', compact('stats', 'recentAppointments', 'chartData', 'company', 'monthOptions'));
    }

    public function monthChart(Request $request): JsonResponse
    {
        /** @var \App\Models\Company $company */
        $company = Auth::guard('company')->user();

        $year  = (int) $request->query('year',  now()->year);
        $month = (int) $request->query('month', now()->month);

        $month = max(1, min(12, $month));
        $year  = max(2000, min(2100, $year));

        $data = $this->dashboardStatistics->monthChartForCompany($company, $year, $month);

        return response()->json($data);
    }
}
