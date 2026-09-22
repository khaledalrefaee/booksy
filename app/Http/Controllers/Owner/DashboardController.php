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
        // Branches are eager-loaded (only the columns onboarding needs) so the
        // whole widget costs a handful of batched queries, not ~5 per company.
        $needsHelpCompanies = Company::query()
            ->where('created_at', '>=', $today->copy()->subDays(30))
            ->with('branches:id,company_id,is_head_office,governorate_id,latitude,longitude')
            ->latest()
            ->limit(40)
            ->get();

        $percents = OnboardingService::percentForMany($needsHelpCompanies);

        // One grouped query for the last successful login of every company,
        // instead of a MAX() per row.
        $lastLogins = CompanyLoginActivity::query()
            ->whereIn('company_id', $needsHelpCompanies->pluck('id'))
            ->where('successful', true)
            ->groupBy('company_id')
            ->selectRaw('company_id, MAX(created_at) as last_login')
            ->pluck('last_login', 'company_id');

        $needsHelp = $needsHelpCompanies
            ->map(function (Company $c) use ($percents, $lastLogins) {
                $lastLogin = $lastLogins[$c->id] ?? null;

                return [
                    'company'      => $c,
                    'percent'      => $percents[$c->id] ?? 0,
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
