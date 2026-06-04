<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Services\Owner\DashboardStatisticsService;
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

        return view('owner.index', compact('stats', 'recentAppointments', 'chartData'));
    }
}
