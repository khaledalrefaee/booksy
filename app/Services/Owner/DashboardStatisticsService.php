<?php

namespace App\Services\Owner;

use App\Models\Appointment;
use App\Models\Branch;
use App\Models\BranchWorkingHour;
use App\Models\Company;
use App\Models\Service;
use App\Models\WaitlistEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class DashboardStatisticsService
{
    /**
     * @return array{appointments_total: int, appointments_pending: int, branches: int, services: int, waitlist_waiting: int}
     */
    /**
     * Platform-wide stats for the owner dashboard.
     *
     * @return array{appointments_total: int, appointments_pending: int, companies: int, branches: int, services: int, waitlist_waiting: int}
     */
    public function forPlatform(): array
    {
        return [
            'appointments_total' => Appointment::query()->count(),
            'appointments_pending' => Appointment::query()->where('status', 'pending')->count(),
            'companies' => Company::query()->count(),
            'branches' => Branch::query()->count(),
            'services' => Service::query()->count(),
            'waitlist_waiting' => WaitlistEntry::query()->where('status', 'waiting')->count(),
        ];
    }

    public function forCompany(Company $company): array
    {
        $branchIds = $company->branches()->pluck('id');

        return [
            'appointments_total' => Appointment::query()->where('company_id', $company->id)->count(),
            'appointments_pending' => Appointment::query()
                ->where('company_id', $company->id)
                ->where('status', 'pending')
                ->count(),
            'branches' => $company->branches()->count(),
            'services' => Service::query()->whereIn('branch_id', $branchIds)->count(),
            'waitlist_waiting' => WaitlistEntry::query()
                ->where('company_id', $company->id)
                ->where('status', 'waiting')
                ->count(),
        ];
    }

    public function chartDataForPlatform(): array
    {
        return $this->buildChartData(null);
    }

    /**
     * Series data for ApexCharts on the owner dashboard.
     *
     * @return array{
     *     daily: array{labels: list<string>, total: list<int>, pending: list<int>, completed: list<int>},
     *     monthly: array{labels: list<string>, total: list<int>},
     *     status: array{labels: list<string>, values: list<int>},
     *     sparkline: array{total: list<int>, pending: list<int>, completed: list<int>}
     * }
     */
    public function chartDataForCompany(Company $company): array
    {
        return $this->buildChartData($company->id, $company);
    }

    public function monthChartForCompany(Company $company, int $year, int $month): array
    {
        return $this->buildMonthDailySeries($year, $month, $company->id);
    }

    private function buildMonthDailySeries(int $year, int $month, ?int $companyId): array
    {
        $tz    = config('app.timezone');
        $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $query = Appointment::query()->whereBetween('start_time', [$start, $end]);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $rows  = $query->get(['start_time', 'status']);
        $byDay = $rows->groupBy(
            fn (Appointment $r) => (int) $r->start_time?->timezone($tz)->format('j')
        );

        $labels = $total = $pending = $completed = [];

        for ($d = 1; $d <= $start->daysInMonth; $d++) {
            $group       = $byDay->get($d, collect());
            $labels[]    = (string) $d;
            $total[]     = $group->count();
            $pending[]   = $group->where('status', 'pending')->count();
            $completed[] = $group->where('status', 'completed')->count();
        }

        return compact('labels', 'total', 'pending', 'completed');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChartData(?int $companyId, ?Company $company = null): array
    {
        $tz = config('app.timezone');
        $now = now($tz);

        $recentQuery = Appointment::query()
            ->where('start_time', '>=', $now->copy()->subDays(29)->startOfDay())
            ->where('start_time', '<=', $now->copy()->addDays(7)->endOfDay());

        if ($companyId !== null) {
            $recentQuery->where('company_id', $companyId);
        }

        $recent = $recentQuery->get(['start_time', 'status', 'total_price']);

        $daily = $this->buildDailySeries($recent, $now, $tz, 30);
        $monthly = $this->buildMonthlySeries($now, $tz, 12, $companyId, $company?->created_at);
        $status = $this->buildStatusBreakdown($companyId);
        $revenue = $this->buildRevenueSeries($now, $tz, 12, $companyId);

        $today     = $this->buildTodaySeries($now, $tz, $companyId, $company);
        $weekly    = $this->buildWeeklySeries($now, $tz, $companyId);
        $yearly    = $this->buildYearlySeries($now, $tz, $companyId, $company?->created_at);
        $byStatus  = $this->buildStatusCounts($companyId);

        return [
            'today'   => $today,
            'week'    => $weekly,
            'month'   => $daily,
            'year'    => $yearly,
            'daily'   => $daily,
            'monthly' => $monthly,
            'status'  => $status,
            'revenue' => $revenue,
            'by_status' => $byStatus,
            'sparkline' => [
                'total'     => array_slice($daily['total'], -11),
                'pending'   => array_slice($daily['pending'], -11),
                'completed' => array_slice($daily['completed'], -11),
            ],
        ];
    }

    /**
     * Empty chart payload when no company is selected.
     *
     * @return array<string, mixed>
     */
    public function emptyChartData(): array
    {
        $labels = [];
        $zeros = [];

        for ($i = 29; $i >= -7; $i--) {
            $labels[] = now()->subDays($i)->format('d');
            $zeros[] = 0;
        }

        $monthlyLabels = [];
        $monthlyZeros = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthlyLabels[] = (string) now()->subMonths($i)->month;
            $monthlyZeros[] = 0;
        }

        $dailyData = ['labels' => $labels, 'total' => $zeros, 'pending' => $zeros, 'completed' => $zeros];
        $monthlyData = ['labels' => $monthlyLabels, 'total' => $monthlyZeros];

        // Today: working hours only (8:00 – 22:00 default)
        $hourLabels = array_map(fn($h) => sprintf('%02d:00', $h), range(8, 22));
        $hourZeros  = array_fill(0, 15, 0);
        $todayData  = ['labels' => $hourLabels, 'total' => $hourZeros, 'pending' => $hourZeros, 'completed' => $hourZeros];

        // Week: last 7 days zeros
        $weekLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $weekLabels[] = now()->subDays($i)->format('D d M');
        }
        $weekZeros = array_fill(0, 7, 0);
        $weekData  = ['labels' => $weekLabels, 'total' => $weekZeros, 'pending' => $weekZeros, 'completed' => $weekZeros];

        return [
            'today'   => $todayData,
            'week'    => $weekData,
            'month'   => $dailyData,
            'year'    => $monthlyData,
            'daily'   => $dailyData,
            'monthly' => $monthlyData,
            'status'  => ['labels' => [], 'values' => []],
            'by_status' => ['pending' => 0, 'confirmed' => 0, 'completed' => 0, 'cancelled' => 0],
            'revenue' => ['labels' => $monthlyLabels, 'total' => $monthlyZeros],
            'sparkline' => [
                'total'     => array_slice($zeros, -11),
                'pending'   => array_slice($zeros, -11),
                'completed' => array_slice($zeros, -11),
            ],
        ];
    }

    /**
     * @param  Collection<int, Appointment>  $appointments
     * @return array{labels: list<string>, total: list<int>, pending: list<int>, completed: list<int>}
     */
    private function buildDailySeries(Collection $appointments, Carbon $now, string $tz, int $days): array
    {
        $byDay = $appointments->groupBy(
            fn (Appointment $row) => $row->start_time?->timezone($tz)->format('Y-m-d') ?? ''
        );

        $labels = [];
        $total = [];
        $pending = [];
        $completed = [];

        for ($i = $days - 1; $i >= -7; $i--) {
            $day = $now->copy()->subDays($i);
            $key = $day->format('Y-m-d');
            $rows = $byDay->get($key, collect());

            $labels[] = $day->format('d');
            $total[] = $rows->count();
            $pending[] = $rows->where('status', 'pending')->count();
            $completed[] = $rows->where('status', 'completed')->count();
        }

        return compact('labels', 'total', 'pending', 'completed');
    }

    /**
     * @return array{labels: list<string>, total: list<float>}
     */
    private function buildRevenueSeries(Carbon $now, string $tz, int $months, ?int $companyId = null): array
    {
        $from = $now->copy()->subMonths($months - 1)->startOfMonth();

        $rowsQuery = Appointment::query()
            ->where('start_time', '>=', $from)
            ->whereIn('status', ['completed', 'confirmed'])
            ->whereNotNull('total_price');

        if ($companyId !== null) {
            $rowsQuery->where('company_id', $companyId);
        }

        $rows = $rowsQuery->get(['start_time', 'total_price']);

        $byMonth = $rows->groupBy(
            fn (Appointment $row) => $row->start_time?->timezone($tz)->format('Y-m') ?? ''
        );

        $labels = [];
        $total = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $key = $month->format('Y-m');

            $labels[] = $month->translatedFormat('M Y');
            $total[] = round((float) $byMonth->get($key, collect())->sum('total_price'), 2);
        }

        return compact('labels', 'total');
    }

    /**
     * @return array{labels: list<string>, total: list<int>}
     */
    private function buildMonthlySeries(Carbon $now, string $tz, int $months, ?int $companyId = null, ?Carbon $companyCreatedAt = null): array
    {
        $from = $now->copy()->subMonths($months - 1)->startOfMonth();

        $rowsQuery = Appointment::query()->where('start_time', '>=', $from);

        if ($companyId !== null) {
            $rowsQuery->where('company_id', $companyId);
        }

        $rows = $rowsQuery->get(['start_time']);

        $byMonth = $rows->groupBy(
            fn (Appointment $row) => $row->start_time?->timezone($tz)->format('Y-m') ?? ''
        );

        // Show year only when account is >= 12 months old (spans two calendar years)
        $accountAgeMonths = $companyCreatedAt ? $companyCreatedAt->diffInMonths($now) : 0;
        $showYear = $accountAgeMonths >= 12;

        $labels = [];
        $total = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $key = $month->format('Y-m');

            // If account < 1 year: plain month number. If >= 1 year: "Jan '24" style.
            $labels[] = $showYear
                ? $month->translatedFormat("M 'y")
                : (string) $month->month;
            $total[] = $byMonth->get($key, collect())->count();
        }

        return compact('labels', 'total');
    }

    /** Today: hourly breakdown limited to company working hours */
    private function buildTodaySeries(Carbon $now, string $tz, ?int $companyId, ?Company $company = null): array
    {
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $query = Appointment::query()
            ->whereBetween('start_time', [$start, $end]);

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $rows = $query->get(['start_time', 'status']);

        $byHour = $rows->groupBy(
            fn (Appointment $r) => (int) $r->start_time?->timezone($tz)->format('G')
        );

        // Determine working hour range from branch working hours (today's day of week)
        $todayDow = (int) $now->dayOfWeek; // 0=Sun … 6=Sat
        $hourFrom = 8;
        $hourTo   = 22;

        if ($company !== null) {
            $branchIds = $company->branches()->pluck('id');

            $todayHours = BranchWorkingHour::query()
                ->whereIn('branch_id', $branchIds)
                ->where('day_of_week', $todayDow)
                ->where('is_open', true)
                ->get(['open_time', 'close_time']);

            if ($todayHours->isNotEmpty()) {
                $opens  = $todayHours->map(fn ($r) => (int) substr($r->open_time, 0, 2));
                $closes = $todayHours->map(fn ($r) => (int) substr($r->close_time, 0, 2));
                $hourFrom = $opens->min();
                $hourTo   = min($closes->max(), 23);
            }
        }

        $labels = [];
        $total  = [];
        $pending   = [];
        $completed = [];

        for ($h = $hourFrom; $h <= $hourTo; $h++) {
            $group = $byHour->get($h, collect());
            $labels[]    = sprintf('%02d:00', $h);
            $total[]     = $group->count();
            $pending[]   = $group->where('status', 'pending')->count();
            $completed[] = $group->where('status', 'completed')->count();
        }

        return compact('labels', 'total', 'pending', 'completed');
    }

    /** Week: last 7 days */
    private function buildWeeklySeries(Carbon $now, string $tz, ?int $companyId): array
    {
        $start = $now->copy()->subDays(6)->startOfDay();

        $query = Appointment::query()
            ->where('start_time', '>=', $start)
            ->where('start_time', '<=', $now->copy()->endOfDay());

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $rows = $query->get(['start_time', 'status']);

        $byDay = $rows->groupBy(
            fn (Appointment $r) => $r->start_time?->timezone($tz)->format('Y-m-d') ?? ''
        );

        $labels = [];
        $total  = [];
        $pending   = [];
        $completed = [];

        for ($i = 6; $i >= 0; $i--) {
            $day   = $now->copy()->subDays($i);
            $key   = $day->format('Y-m-d');
            $group = $byDay->get($key, collect());

            $labels[]    = $day->translatedFormat('D d M');
            $total[]     = $group->count();
            $pending[]   = $group->where('status', 'pending')->count();
            $completed[] = $group->where('status', 'completed')->count();
        }

        return compact('labels', 'total', 'pending', 'completed');
    }

    /** Year: last 12 months (same as monthly but named "year" range) */
    private function buildYearlySeries(Carbon $now, string $tz, ?int $companyId, ?Carbon $companyCreatedAt = null): array
    {
        return $this->buildMonthlySeries($now, $tz, 12, $companyId, $companyCreatedAt);
    }

    /** Returns raw status → count map (no translation). */
    private function buildStatusCounts(?int $companyId = null): array
    {
        $query = Appointment::query()->selectRaw('status, COUNT(*) as cnt');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $rows = $query->groupBy('status')->pluck('cnt', 'status');

        return [
            'pending'   => (int) ($rows['pending']   ?? 0),
            'confirmed' => (int) ($rows['confirmed']  ?? 0),
            'completed' => (int) ($rows['completed']  ?? 0),
            'cancelled' => (int) ($rows['cancelled']  ?? 0),
            'rejected'  => (int) ($rows['rejected']   ?? 0),
            'no_show'   => (int) ($rows['no_show']    ?? 0),
        ];
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function buildStatusBreakdown(?int $companyId = null): array
    {
        $countsQuery = Appointment::query()->selectRaw('status, COUNT(*) as aggregate');

        if ($companyId !== null) {
            $countsQuery->where('company_id', $companyId);
        }

        $counts = $countsQuery
            ->groupBy('status')
            ->orderByDesc('aggregate')
            ->pluck('aggregate', 'status');

        $labels = [];
        $values = [];

        foreach ($counts as $status => $count) {
            $labels[] = (string) __($status);
            $values[] = (int) $count;
        }

        return compact('labels', 'values');
    }
}
