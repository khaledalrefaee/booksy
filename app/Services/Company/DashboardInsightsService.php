<?php

namespace App\Services\Company;

use App\Enums\BookingSource;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Whole-business numbers for the company dashboard: money, customers, team,
 * service mix, demand shape, booking channels, reputation and the things that
 * need attention. Everything is aggregated in SQL (GROUP BY) and cached for a
 * few minutes — same strategy as {@see \App\Services\Owner\DashboardStatisticsService}.
 *
 * Revenue means *realised* revenue: the total_price of completed appointments.
 * Money that is booked but not yet collected is reported separately as
 * "expected".
 */
final class DashboardInsightsService
{
    private const CACHE_TTL = 300; // 5 minutes

    /** Statuses whose revenue is already in the till. */
    private const DONE = ['completed'];

    /** Statuses that mean the visit did not happen. */
    private const LOST = ['cancelled_by_customer', 'cancelled_by_salon', 'no_show'];

    /** Booked, still to be delivered or collected. */
    private const OPEN = ['pending', 'confirmed', 'upcoming', 'arrived', 'in_progress', 'paused', 'awaiting_payment'];

    /** Rolling window used by the "shape of the business" widgets. */
    private const WINDOW_DAYS = 90;

    public function forCompany(Company $company): array
    {
        return Cache::remember(
            self::cacheKey($company->id, app()->getLocale()),
            self::CACHE_TTL,
            fn () => $this->build($company),
        );
    }

    /** Booking + revenue series for one calendar month (month picker endpoint). */
    public function monthSeries(Company $company, int $year, int $month): array
    {
        return Cache::remember(
            self::monthKey($company->id, $year, $month),
            self::CACHE_TTL,
            function () use ($company, $year, $month) {
                $tz    = config('app.timezone');
                $start = Carbon::create($year, $month, 1, 0, 0, 0, $tz)->startOfMonth();
                $end   = $start->copy()->endOfMonth();
                $rows  = $this->grouped($company->id, $start, $end, 'DAY(start_time)');

                $labels = $bookings = $revenue = [];
                for ($d = 1; $d <= $start->daysInMonth; $d++) {
                    $row        = $rows->get($d);
                    $labels[]   = (string) $d;
                    $bookings[] = (int) ($row->bookings ?? 0);
                    $revenue[]  = round((float) ($row->revenue ?? 0), 2);
                }

                return compact('labels', 'bookings', 'revenue');
            },
        );
    }

    /** @return list<string> every cache key this service owns for a company */
    public static function cacheKeys(int $companyId, ?Carbon $when = null): array
    {
        $when ??= now();
        $keys = [self::monthKey($companyId, $when->year, $when->month)];
        foreach (['ar', 'en'] as $locale) {
            $keys[] = self::cacheKey($companyId, $locale);
        }

        return $keys;
    }

    private static function cacheKey(int $companyId, string $locale): string
    {
        return "dash.company.{$companyId}.insights.v1.{$locale}";
    }

    private static function monthKey(int $companyId, int $year, int $month): string
    {
        return "dash.company.{$companyId}.monthseries.v1.{$year}.{$month}";
    }

    // ─────────────────────────────────────────────────────────────────────

    private function build(Company $company): array
    {
        $tz  = config('app.timezone');
        $now = now($tz);
        $cid = $company->id;

        $monthStart = $now->copy()->startOfMonth();
        $elapsed    = (int) $monthStart->diffInSeconds($now);
        $prevStart  = $monthStart->copy()->subMonthNoOverflow();
        $prevEndFull = $monthStart->copy()->subSecond();
        // Compare like with like: this month so far vs the same stretch of last month.
        $prevEnd    = $prevStart->copy()->addSeconds($elapsed)->min($prevEndFull);

        $cur  = $this->aggregate($cid, $monthStart, $now->copy()->endOfMonth());
        $prev = $this->aggregate($cid, $prevStart, $prevEnd);

        $expected = (float) $this->base($cid)
            ->whereBetween('start_time', [$now, $now->copy()->endOfMonth()])
            ->whereIn('status', self::OPEN)
            ->sum('total_price');

        $branchIds = Branch::query()->where('company_id', $cid)->pluck('id');

        return [
            'currency'   => $this->currency(),
            'kpi'        => [
                'revenue'          => round($cur['revenue'], 2),
                'revenue_prev'     => round($prev['revenue'], 2),
                'revenue_delta'    => $this->delta($cur['revenue'], $prev['revenue']),
                'revenue_expected' => round($expected, 2),
                'bookings'         => $cur['bookings'],
                'bookings_prev'    => $prev['bookings'],
                'bookings_delta'   => $this->delta($cur['bookings'], $prev['bookings']),
                'avg_ticket'       => $cur['completed'] > 0 ? round($cur['revenue'] / $cur['completed'], 2) : 0.0,
                'avg_ticket_prev'  => $prev['completed'] > 0 ? round($prev['revenue'] / $prev['completed'], 2) : 0.0,
            ],
            'customers'  => $this->customers($cid, $monthStart, $prevStart),
            'today'      => $this->today($cid, $now),
            'series'     => $this->series($cid, $now),
            'health'     => $this->health($cid, $now),
            'services'   => $this->topServices($cid, $now),
            'staff'      => $this->topStaff($cid, $now),
            'branches'   => $branchIds->count() > 1 ? $this->branchRanking($cid, $now) : [],
            'heatmap'    => $this->heatmap($cid, $now),
            'sources'    => $this->sources($cid, $now),
            'reviews'    => $this->reviews($branchIds),
            'team'       => ['active' => Employee::query()->where('company_id', $cid)->where('is_active', true)->count()],
            'finance'    => $company->hasFeature('finance') ? $this->finance($cid, $now) : null,
            'inventory'  => $company->hasFeature('inventory') ? $this->inventory($cid) : null,
            'attention'  => $this->attention($cid),
        ];
    }

    // ── building blocks ──────────────────────────────────────────────────

    /** Appointments that are real bookings (drafts are abandoned reception forms). */
    private function base(int $companyId)
    {
        return Appointment::query()->where('company_id', $companyId)->where('status', '!=', 'draft');
    }

    /** @return array{bookings:int, completed:int, lost:int, revenue:float, customers:int} */
    private function aggregate(int $cid, Carbon $from, Carbon $to): array
    {
        $row = $this->base($cid)
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status IN ('cancelled_by_customer','cancelled_by_salon','no_show') THEN 1 ELSE 0 END) as lost")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN COALESCE(total_price,0) ELSE 0 END) as revenue")
            ->selectRaw('COUNT(DISTINCT customer_id) as customers')
            ->first();

        return [
            'bookings'  => (int) ($row->bookings ?? 0),
            'completed' => (int) ($row->completed ?? 0),
            'lost'      => (int) ($row->lost ?? 0),
            'revenue'   => (float) ($row->revenue ?? 0),
            'customers' => (int) ($row->customers ?? 0),
        ];
    }

    /** Percentage change, null when there is no baseline to compare against. */
    private function delta(float|int $current, float|int $previous): ?float
    {
        if ($previous <= 0) {
            return $current > 0 ? null : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    private function currency(): array
    {
        $code = config('booksy.default_currency', 'SYP');

        return [
            'code'   => $code,
            'symbol' => config("booksy.currencies.{$code}.symbol", $code),
        ];
    }

    /** One grouped pass: bookings + realised revenue per bucket. */
    private function grouped(int $cid, Carbon $from, Carbon $to, string $keyExpr): Collection
    {
        return $this->base($cid)
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw("{$keyExpr} as k")
            ->selectRaw('COUNT(*) as bookings')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN COALESCE(total_price,0) ELSE 0 END) as revenue")
            ->groupBy('k')
            ->get()
            ->keyBy('k');
    }

    /** @return array{total:int, new_month:int, new_prev:int, returning:int, repeat_rate:float} */
    private function customers(int $cid, Carbon $monthStart, Carbon $prevStart): array
    {
        $per = $this->base($cid)
            ->whereNotNull('customer_id')
            ->whereNotIn('status', self::LOST)
            ->selectRaw('customer_id, COUNT(*) as visits, MIN(created_at) as first_at')
            ->groupBy('customer_id');

        $row = DB::query()->fromSub($per, 't')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN visits >= 2 THEN 1 ELSE 0 END) as returning_c')
            ->selectRaw('SUM(CASE WHEN first_at >= ? THEN 1 ELSE 0 END) as new_month', [$monthStart])
            ->selectRaw('SUM(CASE WHEN first_at >= ? AND first_at < ? THEN 1 ELSE 0 END) as new_prev', [$prevStart, $monthStart])
            ->first();

        $total = (int) ($row->total ?? 0);
        $ret   = (int) ($row->returning_c ?? 0);

        return [
            'total'       => $total,
            'new_month'   => (int) ($row->new_month ?? 0),
            'new_prev'    => (int) ($row->new_prev ?? 0),
            'returning'   => $ret,
            'repeat_rate' => $total > 0 ? round($ret / $total * 100, 1) : 0.0,
        ];
    }

    private function today(int $cid, Carbon $now): array
    {
        $rows = $this->base($cid)
            ->whereBetween('start_time', [$now->copy()->startOfDay(), $now->copy()->endOfDay()])
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as done")
            ->selectRaw("SUM(CASE WHEN status IN ('cancelled_by_customer','cancelled_by_salon','no_show') THEN 1 ELSE 0 END) as lost")
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN COALESCE(total_price,0) ELSE 0 END) as revenue")
            ->first();

        $next = $this->base($cid)
            ->where('start_time', '>=', $now)
            ->whereBetween('start_time', [$now, $now->copy()->endOfDay()])
            ->whereNotIn('status', self::LOST)
            ->with(['customer:id,name', 'service:id,name_en,name_ar'])
            ->orderBy('start_time')
            ->first();

        $total = (int) ($rows->total ?? 0);
        $lost  = (int) ($rows->lost ?? 0);

        return [
            'total'   => $total,
            'done'    => (int) ($rows->done ?? 0),
            'lost'    => $lost,
            'left'    => max(0, $total - (int) ($rows->done ?? 0) - $lost),
            'revenue' => round((float) ($rows->revenue ?? 0), 2),
            'next'    => $next ? [
                'time'     => $next->start_time?->format('H:i'),
                'customer' => $next->customer?->name ?? $next->customer_name,
                'service'  => $next->service?->localizedName(),
                'url'      => route('company.appointments.show', $next),
            ] : null,
        ];
    }

    /**
     * Performance chart payloads. The month view is separate (month picker),
     * but its current month is inlined so the first paint needs no request.
     */
    private function series(int $cid, Carbon $now): array
    {
        // ── last 14 days (week view + KPI sparklines) ──
        $from  = $now->copy()->subDays(13)->startOfDay();
        $daily = $this->grouped($cid, $from, $now->copy()->endOfDay(), 'DATE(start_time)');

        $dLabels = $dBookings = $dRevenue = [];
        for ($i = 13; $i >= 0; $i--) {
            $day         = $now->copy()->subDays($i);
            $row         = $daily->get($day->format('Y-m-d'));
            $dLabels[]   = $day->translatedFormat('D j');
            $dBookings[] = (int) ($row->bookings ?? 0);
            $dRevenue[]  = round((float) ($row->revenue ?? 0), 2);
        }

        // ── today, per hour ──
        $byHour = $this->grouped($cid, $now->copy()->startOfDay(), $now->copy()->endOfDay(), 'HOUR(start_time)');
        $hours  = $byHour->keys()->map(fn ($h) => (int) $h);
        $hFrom  = min(8, (int) ($hours->min() ?? 8));
        $hTo    = max(20, (int) ($hours->max() ?? 20));

        $tLabels = $tBookings = $tRevenue = [];
        for ($h = $hFrom; $h <= $hTo; $h++) {
            $row         = $byHour->get($h);
            $tLabels[]   = sprintf('%02d:00', $h);
            $tBookings[] = (int) ($row->bookings ?? 0);
            $tRevenue[]  = round((float) ($row->revenue ?? 0), 2);
        }

        // ── last 12 months ──
        $yFrom  = $now->copy()->subMonthsNoOverflow(11)->startOfMonth();
        $byMon  = $this->grouped($cid, $yFrom, $now->copy()->endOfMonth(), "DATE_FORMAT(start_time, '%Y-%m')");
        $yLabels = $yBookings = $yRevenue = [];
        for ($i = 11; $i >= 0; $i--) {
            $m           = $now->copy()->startOfMonth()->subMonthsNoOverflow($i);
            $row         = $byMon->get($m->format('Y-m'));
            $yLabels[]   = $m->translatedFormat('M');
            $yBookings[] = (int) ($row->bookings ?? 0);
            $yRevenue[]  = round((float) ($row->revenue ?? 0), 2);
        }

        return [
            'today' => ['labels' => $tLabels, 'bookings' => $tBookings, 'revenue' => $tRevenue],
            'week'  => [
                'labels'   => array_slice($dLabels, -7),
                'bookings' => array_slice($dBookings, -7),
                'revenue'  => array_slice($dRevenue, -7),
            ],
            'month' => $this->monthSeriesFor($cid, $now),
            'year'  => ['labels' => $yLabels, 'bookings' => $yBookings, 'revenue' => $yRevenue],
            'spark' => ['labels' => $dLabels, 'bookings' => $dBookings, 'revenue' => $dRevenue],
        ];
    }

    private function monthSeriesFor(int $cid, Carbon $now): array
    {
        $start = $now->copy()->startOfMonth();
        $rows  = $this->grouped($cid, $start, $now->copy()->endOfMonth(), 'DAY(start_time)');

        $labels = $bookings = $revenue = [];
        for ($d = 1; $d <= $start->daysInMonth; $d++) {
            $row        = $rows->get($d);
            $labels[]   = (string) $d;
            $bookings[] = (int) ($row->bookings ?? 0);
            $revenue[]  = round((float) ($row->revenue ?? 0), 2);
        }

        return compact('labels', 'bookings', 'revenue');
    }

    /**
     * Outcome mix of the last 90 days: the donut, the completion rate and the
     * no-show / cancellation rates all come from one grouped query.
     */
    private function health(int $cid, Carbon $now): array
    {
        $by = $this->base($cid)
            ->whereBetween('start_time', [$now->copy()->subDays(self::WINDOW_DAYS)->startOfDay(), $now])
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $n = fn (array $keys) => (int) collect($keys)->sum(fn ($k) => (int) ($by[$k] ?? 0));

        $completed = $n(['completed']);
        $active    = $n(['confirmed', 'upcoming', 'arrived', 'in_progress', 'paused']);
        $pending   = $n(['pending']);
        $awaiting  = $n(['awaiting_payment']);
        $cancelled = $n(['cancelled_by_customer', 'cancelled_by_salon']);
        $noShow    = $n(['no_show']);
        $total     = $completed + $active + $pending + $awaiting + $cancelled + $noShow;
        $closed    = $completed + $cancelled + $noShow;

        $pct = fn (int $part, int $whole) => $whole > 0 ? round($part / $whole * 100, 1) : 0.0;

        return [
            'days'            => self::WINDOW_DAYS,
            'total'           => $total,
            'completed'       => $completed,
            'active'          => $active,
            'pending'         => $pending,
            'awaiting'        => $awaiting,
            'cancelled'       => $cancelled,
            'no_show'         => $noShow,
            'completion_rate' => $pct($completed, $closed),
            'cancel_rate'     => $pct($cancelled, $closed),
            'no_show_rate'    => $pct($noShow, $closed),
        ];
    }

    private function topServices(int $cid, Carbon $now): array
    {
        $rows = $this->base($cid)
            ->whereNotNull('service_id')
            ->whereNotIn('status', self::LOST)
            ->whereBetween('start_time', [$now->copy()->subDays(self::WINDOW_DAYS)->startOfDay(), $now])
            ->selectRaw('service_id, COUNT(*) as c')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN COALESCE(total_price,0) ELSE 0 END) as revenue")
            ->groupBy('service_id')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        $names = Service::query()->whereIn('id', $rows->pluck('service_id'))->get()->keyBy('id');

        return $rows->map(fn ($r) => [
            'name'    => $names[$r->service_id]?->localizedName() ?? '—',
            'count'   => (int) $r->c,
            'revenue' => round((float) $r->revenue, 2),
        ])->all();
    }

    private function topStaff(int $cid, Carbon $now): array
    {
        $rows = $this->base($cid)
            ->whereNotNull('employee_id')
            ->whereNotIn('status', self::LOST)
            ->whereBetween('start_time', [$now->copy()->subDays(self::WINDOW_DAYS)->startOfDay(), $now])
            ->selectRaw('employee_id, COUNT(*) as c')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN COALESCE(total_price,0) ELSE 0 END) as revenue")
            ->groupBy('employee_id')
            ->orderByDesc('c')
            ->limit(5)
            ->get();

        $names = Employee::query()->whereIn('id', $rows->pluck('employee_id'))->get()->keyBy('id');

        return $rows->map(fn ($r) => [
            'name'    => $names[$r->employee_id]?->localizedName() ?? '—',
            'count'   => (int) $r->c,
            'revenue' => round((float) $r->revenue, 2),
        ])->all();
    }

    private function branchRanking(int $cid, Carbon $now): array
    {
        $rows = $this->base($cid)
            ->whereNotIn('status', self::LOST)
            ->whereBetween('start_time', [$now->copy()->subDays(self::WINDOW_DAYS)->startOfDay(), $now])
            ->selectRaw('branch_id, COUNT(*) as c')
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN COALESCE(total_price,0) ELSE 0 END) as revenue")
            ->groupBy('branch_id')
            ->orderByDesc('c')
            ->limit(6)
            ->get();

        $names = Branch::query()->whereIn('id', $rows->pluck('branch_id'))->get()->keyBy('id');

        return $rows->map(fn ($r) => [
            'name'    => $names[$r->branch_id]?->localizedName() ?? '—',
            'count'   => (int) $r->c,
            'revenue' => round((float) $r->revenue, 2),
        ])->all();
    }

    /**
     * Demand shape: bookings per weekday × hour over the last 90 days.
     * Day index follows MySQL DAYOFWEEK (1 = Sunday … 7 = Saturday).
     */
    private function heatmap(int $cid, Carbon $now): array
    {
        $rows = $this->base($cid)
            ->whereNotIn('status', self::LOST)
            ->whereBetween('start_time', [$now->copy()->subDays(self::WINDOW_DAYS)->startOfDay(), $now])
            ->selectRaw('DAYOFWEEK(start_time) as d, HOUR(start_time) as h, COUNT(*) as c')
            ->groupBy('d', 'h')
            ->get();

        if ($rows->isEmpty()) {
            return ['hours' => [], 'days' => [], 'max' => 0, 'peak' => null];
        }

        $hFrom = max(0, min(9, (int) $rows->min('h')));
        $hTo   = min(23, max(20, (int) $rows->max('h')));

        $grid = [];
        foreach ($rows as $r) {
            $grid[(int) $r->d][(int) $r->h] = (int) $r->c;
        }

        $sunday = $now->copy()->startOfWeek(Carbon::SUNDAY);
        $days   = [];
        for ($d = 1; $d <= 7; $d++) {
            $cells = [];
            for ($h = $hFrom; $h <= $hTo; $h++) {
                $cells[] = $grid[$d][$h] ?? 0;
            }
            $days[] = [
                'dow'   => $d,
                'label' => $sunday->copy()->addDays($d - 1)->translatedFormat('D'),
                'cells' => $cells,
                'total' => array_sum($cells),
            ];
        }

        $peak = $rows->sortByDesc('c')->first();

        return [
            'hours' => range($hFrom, $hTo),
            'days'  => $days,
            'max'   => (int) $rows->max('c'),
            'peak'  => [
                'day'   => $sunday->copy()->addDays((int) $peak->d - 1)->translatedFormat('l'),
                'hour'  => sprintf('%02d:00', (int) $peak->h),
                'count' => (int) $peak->c,
            ],
        ];
    }

    private function sources(int $cid, Carbon $now): array
    {
        $rows = $this->base($cid)
            ->whereNotIn('status', self::LOST)
            ->whereBetween('start_time', [$now->copy()->subDays(self::WINDOW_DAYS)->startOfDay(), $now])
            ->selectRaw('booking_source as s, COUNT(*) as c')
            ->groupBy('s')
            ->orderByDesc('c')
            ->get();

        return $rows->map(function ($r) {
            $enum = $r->s ? BookingSource::tryFrom($r->s) : null;

            return [
                'key'   => $enum?->value ?? 'other',
                'label' => $enum?->label() ?? __('Other'),
                'count' => (int) $r->c,
            ];
        })->groupBy('key')->map(fn ($g) => [
            'key'   => $g->first()['key'],
            'label' => $g->first()['label'],
            'count' => $g->sum('count'),
        ])->sortByDesc('count')->values()->all();
    }

    private function reviews(Collection $branchIds): array
    {
        if ($branchIds->isEmpty()) {
            return ['avg' => 0.0, 'count' => 0];
        }

        $row = DB::table('reviews')
            ->whereIn('branch_id', $branchIds)
            ->where('is_hidden', false)
            ->selectRaw('COUNT(*) as c, AVG(rating) as a')
            ->first();

        return ['avg' => round((float) ($row->a ?? 0), 1), 'count' => (int) ($row->c ?? 0)];
    }

    private function finance(int $cid, Carbon $now): array
    {
        $debt = DB::table('customer_debts')
            ->where('company_id', $cid)
            ->whereIn('status', ['unpaid', 'partial'])
            ->selectRaw('COUNT(*) as c, COALESCE(SUM(original_amount - paid_amount),0) as owed')
            ->selectRaw('SUM(CASE WHEN due_date IS NOT NULL AND due_date < ? THEN 1 ELSE 0 END) as overdue', [$now->toDateString()])
            ->first();

        return [
            'debts_count'   => (int) ($debt->c ?? 0),
            'debts_owed'    => round((float) ($debt->owed ?? 0), 2),
            'debts_overdue' => (int) ($debt->overdue ?? 0),
        ];
    }

    private function inventory(int $cid): array
    {
        $low = DB::table('branch_stocks as bs')
            ->join('products as p', 'p.id', '=', 'bs.product_id')
            ->where('p.company_id', $cid)
            ->where('p.is_active', true)
            ->where('p.track_stock', true)
            ->whereRaw('bs.quantity <= COALESCE(p.low_stock_threshold, 5)')
            ->count();

        return ['low_stock' => $low];
    }

    private function attention(int $cid): array
    {
        $by = Appointment::query()
            ->where('company_id', $cid)
            ->whereIn('status', ['pending', 'awaiting_payment'])
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        return [
            'pending'  => (int) ($by['pending'] ?? 0),
            'awaiting' => (int) ($by['awaiting_payment'] ?? 0),
        ];
    }
}
