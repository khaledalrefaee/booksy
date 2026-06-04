<?php

namespace App\Services\Owner;

use App\Models\Appointment;
use App\Models\Branch;
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
        return $this->buildChartData($company->id);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildChartData(?int $companyId): array
    {
        $tz = config('app.timezone');
        $now = now($tz);

        $recentQuery = Appointment::query()
            ->where('start_time', '>=', $now->copy()->subDays(29)->startOfDay());

        if ($companyId !== null) {
            $recentQuery->where('company_id', $companyId);
        }

        $recent = $recentQuery->get(['start_time', 'status']);

        $daily = $this->buildDailySeries($recent, $now, $tz, 30);
        $monthly = $this->buildMonthlySeries($now, $tz, 12, $companyId);
        $status = $this->buildStatusBreakdown($companyId);

        return [
            'daily' => $daily,
            'monthly' => $monthly,
            'status' => $status,
            'sparkline' => [
                'total' => array_slice($daily['total'], -11),
                'pending' => array_slice($daily['pending'], -11),
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

        for ($i = 29; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->format('Y-m-d');
            $zeros[] = 0;
        }

        $monthlyLabels = [];
        $monthlyZeros = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthlyLabels[] = now()->subMonths($i)->format('M Y');
            $monthlyZeros[] = 0;
        }

        return [
            'daily' => [
                'labels' => $labels,
                'total' => $zeros,
                'pending' => $zeros,
                'completed' => $zeros,
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'total' => $monthlyZeros,
            ],
            'status' => [
                'labels' => [],
                'values' => [],
            ],
            'sparkline' => [
                'total' => array_slice($zeros, -11),
                'pending' => array_slice($zeros, -11),
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

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = $now->copy()->subDays($i);
            $key = $day->format('Y-m-d');
            $rows = $byDay->get($key, collect());

            $labels[] = $day->translatedFormat('d M');
            $total[] = $rows->count();
            $pending[] = $rows->where('status', 'pending')->count();
            $completed[] = $rows->where('status', 'completed')->count();
        }

        return compact('labels', 'total', 'pending', 'completed');
    }

    /**
     * @return array{labels: list<string>, total: list<int>}
     */
    private function buildMonthlySeries(Carbon $now, string $tz, int $months, ?int $companyId = null): array
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

        $labels = [];
        $total = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $now->copy()->subMonths($i);
            $key = $month->format('Y-m');

            $labels[] = $month->translatedFormat('M Y');
            $total[] = $byMonth->get($key, collect())->count();
        }

        return compact('labels', 'total');
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
