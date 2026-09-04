<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\CompanyLoginActivity;
use App\Services\OnboardingService;
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
            ->limit(30)
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

        // Recent business login/registration activity (owner feed).
        $recentActivity = CompanyLoginActivity::query()
            ->with('company:id,name_en,name_ar')
            ->latest()
            ->limit(40)
            ->get();

        // Businesses that likely need help: signed up recently but setup is
        // still incomplete. Derived — bounded to recent signups to stay cheap.
        $needsHelp = Company::query()
            ->where('created_at', '>=', $today->copy()->subDays(30))
            ->latest()
            ->limit(40)
            ->get()
            ->map(function (Company $c) {
                $lastLogin = $c->loginActivities()->where('successful', true)->max('created_at');

                return [
                    'company'      => $c,
                    'percent'      => OnboardingService::percent($c),
                    'last_login'   => $lastLogin ? Carbon::parse($lastLogin) : null,
                    'days_old'     => (int) $c->created_at->diffInDays(now()),
                ];
            })
            ->filter(fn ($row) => $row['percent'] < 100)
            ->sortBy('percent')
            ->take(24)
            ->values();

        return view('owner.index', compact(
            'stats', 'recentAppointments', 'chartData', 'alerts', 'recentActivity', 'needsHelp'
        ));
    }
}
