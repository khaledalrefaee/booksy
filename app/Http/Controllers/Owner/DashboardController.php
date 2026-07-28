<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Company;
use App\Services\Owner\DashboardStatisticsService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardStatisticsService $dashboardStatistics,
    ) {}

    public function index(): View
    {
        $stats = $this->dashboardStatistics->forPlatform();
        $chartData = $this->dashboardStatistics->chartDataForPlatform();

        $recentAppointments = Appointment::query()
            ->with(['branch', 'customer', 'service', 'service.serviceCategory'])
            ->orderByDesc('start_time')
            ->limit(6)
            ->get();

        $today = Carbon::today();

        $alerts = [
            'pending_companies' => Company::query()->where('status', 'pending')->count(),
            'expiring_soon'     => Company::query()->whereNotNull('plan_id')
                ->whereBetween('plan_expires_at', [$today, $today->copy()->addDays(7)])
                ->count(),
            'expired'           => Company::query()->whereNotNull('plan_id')
                ->whereDate('plan_expires_at', '<', $today)
                ->count(),
        ];

        return view('owner.index', compact('stats', 'recentAppointments', 'chartData', 'alerts'));
    }
}
