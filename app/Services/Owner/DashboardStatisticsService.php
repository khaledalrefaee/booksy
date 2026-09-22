<?php

namespace App\Services\Owner;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\BranchWorkingHour;
use App\Models\Company;
use App\Models\Service;
use App\Models\WaitlistEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class DashboardStatisticsService
{
    /**
     * How long dashboard aggregates stay cached. Short enough that the numbers
     * feel live, long enough that a traffic spike doesn't re-run ~20 aggregate
     * queries over the whole appointments table on every page load.
     */
    private const CACHE_TTL = 300; // 5 minutes

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
        return Cache::remember('dash.platform.stats', self::CACHE_TTL, fn () => [
            'appointments_total' => Appointment::query()->count(),
            'appointments_pending' => Appointment::query()->where('status', 'pending')->count(),
            'companies' => Company::query()->count(),
            'branches' => Branch::query()->count(),
            'services' => Service::query()->count(),
            'waitlist_waiting' => WaitlistEntry::query()->where('status', 'waiting')->count(),
        ]);
    }

    public function forCompany(Company $company): array
    {
        return Cache::remember("dash.company.{$company->id}.stats", self::CACHE_TTL, function () use ($company) {
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
        });
    }

    public function chartDataForPlatform(): array
    {
        return Cache::remember(
            'dash.platform.charts.' . app()->getLocale(),
            self::CACHE_TTL,
            fn () => $this->buildChartData(null),
        );
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
        return Cache::remember(
            "dash.company.{$company->id}.charts." . app()->getLocale(),
            self::CACHE_TTL,
            fn () => $this->buildChartData($company->id, $company),
        );
    }

    public function monthChartForCompany(Company $company, int $year, int $month): array
    {
        return Cache::remember(
            "dash.company.{$company->id}.monthchart.{$year}.{$month}",
            self::CACHE_TTL,
            fn () => $this->buildMonthDailySeries($year, $month, $company->id),
        );
    }

    private function buildMonthDailySeries(int $year, int $month, ?int $companyId): array
    {
        $tz    = config('app.timezone');
        $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfMonth();
        $end   = $start->copy()->endOfMonth();

        $byDay = $this->groupedCounts($companyId, $start, $end, 'DAY(start_time)');

        $labels = $total = $pending = $completed = [];

        for ($d = 1; $d <= $start->daysInMonth; $d++) {
            $row         = $byDay->get($d);
            $labels[]    = (string) $d;
            $total[]     = (int) ($row->total ?? 0);
            $pending[]   = (int) ($row->pending ?? 0);
            $completed[] = (int) ($row->completed ?? 0);
        }

        return compact('labels', 'total', 'pending', 'completed');
    }

    /**
     * Bucketed appointment counts computed in SQL (GROUP BY) instead of loading
     * every row into PHP and grouping in memory — the difference between a
     * 12-second dashboard and a sub-second one at 50k+ appointments.
     *
     * @return \Illuminate\Support\Collection<int|string, object{total:int, pending:int, completed:int}>
     *         keyed by the bucket expression's value
     */
    private function groupedCounts(?int $companyId, Carbon $from, Carbon $to, string $keyExpr): Collection
    {
        $query = Appointment::query()
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw("{$keyExpr} as k")
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->groupBy('k');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        return $query->get()->keyBy('k');
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChartData(?int $companyId, ?Company $company = null): array
    {
        $tz = config('app.timezone');
        $now = now($tz);

        $daily = $this->buildDailySeries($companyId, $now, 30);
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
            'by_status' => array_fill_keys(
                [...array_column(AppointmentStatus::cases(), 'value'), 'cancelled_total'],
                0,
            ),
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
    private function buildDailySeries(?int $companyId, Carbon $now, int $days): array
    {
        $from  = $now->copy()->subDays($days - 1)->startOfDay();
        $to    = $now->copy()->addDays(7)->endOfDay();
        $byDay = $this->groupedCounts($companyId, $from, $to, 'DATE(start_time)');

        $labels = [];
        $total = [];
        $pending = [];
        $completed = [];

        for ($i = $days - 1; $i >= -7; $i--) {
            $day = $now->copy()->subDays($i);
            $row = $byDay->get($day->format('Y-m-d'));

            $labels[] = $day->format('d');
            $total[] = (int) ($row->total ?? 0);
            $pending[] = (int) ($row->pending ?? 0);
            $completed[] = (int) ($row->completed ?? 0);
        }

        return compact('labels', 'total', 'pending', 'completed');
    }

    /**
     * @return array{labels: list<string>, total: list<float>}
     */
    private function buildRevenueSeries(Carbon $now, string $tz, int $months, ?int $companyId = null): array
    {
        $from = $now->copy()->subMonths($months - 1)->startOfMonth();
        $to   = $now->copy()->endOfMonth();

        $query = Appointment::query()
            ->whereBetween('start_time', [$from, $to])
            ->whereIn('status', ['completed', 'confirmed'])
            ->whereNotNull('total_price')
            ->selectRaw("DATE_FORMAT(start_time, '%Y-%m') as k")
            ->selectRaw('SUM(total_price) as revenue')
            ->groupBy('k');

        if ($companyId !== null) {
            $query->where('company_id', $companyId);
        }

        $byMonth = $query->get()->keyBy('k');

        $labels = [];
        $total = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);

            $labels[] = $month->translatedFormat('M Y');
            $total[] = round((float) ($byMonth->get($month->format('Y-m'))->revenue ?? 0), 2);
        }

        return compact('labels', 'total');
    }

    /**
     * @return array{labels: list<string>, total: list<int>}
     */
    private function buildMonthlySeries(Carbon $now, string $tz, int $months, ?int $companyId = null, ?Carbon $companyCreatedAt = null): array
    {
        $from = $now->copy()->subMonths($months - 1)->startOfMonth();
        $to   = $now->copy()->endOfMonth();

        $byMonth = $this->groupedCounts($companyId, $from, $to, "DATE_FORMAT(start_time, '%Y-%m')");

        // Show year only when account is >= 12 months old (spans two calendar years)
        $accountAgeMonths = $companyCreatedAt ? $companyCreatedAt->diffInMonths($now) : 0;
        $showYear = $accountAgeMonths >= 12;

        $labels = [];
        $total = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);

            // If account < 1 year: plain month number. If >= 1 year: "Jan '24" style.
            $labels[] = $showYear
                ? $month->translatedFormat("M 'y")
                : (string) $month->month;
            $total[] = (int) ($byMonth->get($month->format('Y-m'))->total ?? 0);
        }

        return compact('labels', 'total');
    }

    /** Today: hourly breakdown limited to company working hours */
    private function buildTodaySeries(Carbon $now, string $tz, ?int $companyId, ?Company $company = null): array
    {
        $start = $now->copy()->startOfDay();
        $end   = $now->copy()->endOfDay();

        $byHour = $this->groupedCounts($companyId, $start, $end, 'HOUR(start_time)');

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
            $row = $byHour->get($h);
            $labels[]    = sprintf('%02d:00', $h);
            $total[]     = (int) ($row->total ?? 0);
            $pending[]   = (int) ($row->pending ?? 0);
            $completed[] = (int) ($row->completed ?? 0);
        }

        return compact('labels', 'total', 'pending', 'completed');
    }

    /** Week: last 7 days */
    private function buildWeeklySeries(Carbon $now, string $tz, ?int $companyId): array
    {
        $start = $now->copy()->subDays(6)->startOfDay();
        $end   = $now->copy()->endOfDay();

        $byDay = $this->groupedCounts($companyId, $start, $end, 'DATE(start_time)');

        $labels = [];
        $total  = [];
        $pending   = [];
        $completed = [];

        for ($i = 6; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $row = $byDay->get($day->format('Y-m-d'));

            $labels[]    = $day->translatedFormat('D d M');
            $total[]     = (int) ($row->total ?? 0);
            $pending[]   = (int) ($row->pending ?? 0);
            $completed[] = (int) ($row->completed ?? 0);
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

        $counts = [];

        foreach (AppointmentStatus::cases() as $case) {
            $counts[$case->value] = (int) ($rows[$case->value] ?? 0);
        }

        // Kept for the dashboard's "other" bucket, which does not care which
        // side called the cancellation off.
        $counts['cancelled_total'] = collect(AppointmentStatus::cancelled())
            ->sum(fn (AppointmentStatus $s) => $counts[$s->value]);

        return $counts;
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
