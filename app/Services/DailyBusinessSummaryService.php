<?php

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Service;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the numbers behind the Daily Business Summary email for a single
 * company, for a single company-local day.
 *
 * Everything here is derived from data already in the system — no new analytics
 * store. The status taxonomy mirrors the rest of the app (see AppointmentStatus):
 *
 *   • "Real" bookings  = confirmed and everything downstream of it. Draft and
 *     Pending are deliberately excluded — a customer request the salon has not
 *     accepted yet is not business that happened.
 *   • "Completed"      = the Completed status only.
 *   • "Cancelled"      = both cancellation variants.
 *   • "Revenue"        = SUM(total_price) of Completed appointments, the same
 *     definition the profit-loss / employee-performance reports use.
 *
 * The day is anchored to the company's timezone ({@see Company::timezone()}),
 * from local 00:00 up to the moment the report is generated.
 */
final class DailyBusinessSummaryService
{
    /** Confirmed → completed: bookings that actually count as business. */
    private const REAL_STATUSES = [
        'confirmed',
        'arrived',
        'in_progress',
        'paused',
        'awaiting_payment',
        'completed',
    ];

    /**
     * Build the full payload for one company.
     *
     * @param  Carbon|null  $now  Override "now" (company-local) — used by tests.
     * @return array<string,mixed>
     */
    public function build(Company $company, ?Carbon $now = null): array
    {
        $tz          = $company->timezone();
        $generatedAt = ($now ? $now->copy() : Carbon::now($tz))->setTimezone($tz);
        $dayStart    = $generatedAt->copy()->startOfDay();

        $cancelledValues = array_map(fn ($c) => $c->value, AppointmentStatus::cancelled());

        // ── Per-branch grouped totals for today (one query) ──────────────────
        $perBranch = $this->branchTotals($company, $dayStart, $generatedAt, $cancelledValues);

        // Names for every active branch, so we can label the table and include
        // branches that had no activity today.
        $branchNames = $company->branches()
            ->orderBy('id')
            ->get(['id', 'name_en', 'name_ar'])
            ->mapWithKeys(fn ($b) => [$b->id => $b->localizedName()]);

        $branches = $branchNames->map(function (string $name, int $id) use ($perBranch) {
            $row = $perBranch->get($id);

            return [
                'name'      => $name,
                'total'     => (int) ($row->real_total ?? 0),
                'completed' => (int) ($row->completed ?? 0),
                'cancelled' => (int) ($row->cancelled ?? 0),
                'revenue'   => round((float) ($row->revenue ?? 0), 2),
            ];
        })->sortByDesc('revenue')->values();

        // ── Company totals = sum across branches ─────────────────────────────
        $summary = [
            'total'         => (int) $branches->sum('total'),
            'completed'     => (int) $branches->sum('completed'),
            'cancelled'     => (int) $branches->sum('cancelled'),
            'revenue'       => round((float) $branches->sum('revenue'), 2),
            'new_customers' => $this->newCustomersToday($company, $dayStart, $generatedAt),
        ];

        $chart      = $this->bookingChart($company, $dayStart, $generatedAt);
        $comparison = $this->comparison($summary['total'], $company, $dayStart);
        $upcoming   = $this->upcoming($company, $generatedAt);
        $topServices = $this->topServices($company, $dayStart, $generatedAt);

        $hasMultipleBranches = $branchNames->count() > 1;

        return [
            'company'               => $company,
            'is_ar'                 => app()->getLocale() === 'ar',
            'tz'                    => $tz,
            'date'                  => $dayStart->copy(),
            'generated_at'          => $generatedAt->copy(),
            'currency'              => $this->currencySymbol($company),
            'summary'               => $summary,
            'chart'                 => $chart,
            'has_multiple_branches' => $hasMultipleBranches,
            'branches'              => $branches,
            'top_services'          => $topServices,
            'upcoming'              => $upcoming,
            'comparison'            => $comparison,
        ];
    }

    /**
     * Does this company have *any* real activity worth mailing about? Guards the
     * "don't email a company with no actual data at all" rule while still sending
     * a legitimate zero-today report to an established business.
     */
    public function hasReportableData(array $data): bool
    {
        $s = $data['summary'];

        return $s['total'] > 0
            || $s['new_customers'] > 0
            || $s['revenue'] > 0
            || $data['upcoming']['count'] > 0
            || collect($data['chart']['days'])->sum('count') > 0;
    }

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Real / completed / cancelled counts and completed-revenue, grouped by
     * branch, for the day window. One SQL pass with conditional aggregates.
     *
     * @return Collection<int,object>
     */
    private function branchTotals(Company $company, Carbon $from, Carbon $to, array $cancelled): Collection
    {
        $realIn      = $this->placeholders(self::REAL_STATUSES);
        $cancelledIn = $this->placeholders($cancelled);

        return Appointment::query()
            ->where('company_id', $company->id)
            ->whereBetween('start_time', [$from, $to])
            ->selectRaw('branch_id')
            ->selectRaw("SUM(CASE WHEN status IN ($realIn) THEN 1 ELSE 0 END) as real_total", self::REAL_STATUSES)
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed")
            ->selectRaw("SUM(CASE WHEN status IN ($cancelledIn) THEN 1 ELSE 0 END) as cancelled", $cancelled)
            ->selectRaw("SUM(CASE WHEN status = 'completed' THEN total_price ELSE 0 END) as revenue")
            ->groupBy('branch_id')
            ->get()
            ->keyBy('branch_id');
    }

    /**
     * Customers whose *first* real appointment with this company happened today.
     * Uses only appointment history (customers are global; a company "owns" a
     * customer through its bookings).
     */
    private function newCustomersToday(Company $company, Carbon $from, Carbon $to): int
    {
        $todayIds = Appointment::query()
            ->where('company_id', $company->id)
            ->whereIn('status', self::REAL_STATUSES)
            ->whereBetween('start_time', [$from, $to])
            ->whereNotNull('customer_id')
            ->distinct()
            ->pluck('customer_id');

        if ($todayIds->isEmpty()) {
            return 0;
        }

        $returningIds = Appointment::query()
            ->where('company_id', $company->id)
            ->whereIn('status', self::REAL_STATUSES)
            ->whereIn('customer_id', $todayIds)
            ->where('start_time', '<', $from)
            ->distinct()
            ->pluck('customer_id');

        return $todayIds->diff($returningIds)->count();
    }

    /**
     * Real-booking counts per day over the last 7 days (today last), so the
     * email can draw a simple bar chart with today highlighted.
     *
     * @return array{days: list<array{label:string,date:string,count:int,is_today:bool}>, max:int}
     */
    private function bookingChart(Company $company, Carbon $dayStart, Carbon $to): array
    {
        $windowStart = $dayStart->copy()->subDays(6);

        $counts = Appointment::query()
            ->where('company_id', $company->id)
            ->whereIn('status', self::REAL_STATUSES)
            ->whereBetween('start_time', [$windowStart, $to])
            ->selectRaw('DATE(start_time) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd');

        $isAr    = app()->getLocale() === 'ar';
        $todayKey = $dayStart->toDateString();
        $days    = [];
        $max     = 0;

        for ($i = 6; $i >= 0; $i--) {
            $day = $dayStart->copy()->subDays($i);
            $key = $day->toDateString();
            $c   = (int) ($counts[$key] ?? 0);
            $max = max($max, $c);

            $days[] = [
                'label'    => $day->locale($isAr ? 'ar' : 'en')->isoFormat('ddd'),
                'date'     => $day->format('m-d'),
                'count'    => $c,
                'is_today' => $key === $todayKey,
            ];
        }

        return ['days' => $days, 'max' => $max];
    }

    /**
     * Compare today's real bookings against the daily average of the previous
     * 7 days. Returns null when there isn't enough history to be meaningful.
     *
     * @return array{avg7:float,diff_pct:int,direction:string}|null
     */
    private function comparison(int $todayTotal, Company $company, Carbon $dayStart): ?array
    {
        $prevStart = $dayStart->copy()->subDays(7);

        $prevTotal = Appointment::query()
            ->where('company_id', $company->id)
            ->whereIn('status', self::REAL_STATUSES)
            ->whereBetween('start_time', [$prevStart, $dayStart])
            ->count();

        $avg7 = $prevTotal / 7;

        if ($avg7 <= 0) {
            return null; // no baseline → hide the comparison section entirely
        }

        $diff = (int) round((($todayTotal - $avg7) / $avg7) * 100);

        return [
            'avg7'      => round($avg7, 1),
            'diff_pct'  => $diff,
            'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat'),
        ];
    }

    /**
     * Upcoming (future) real bookings + the nearest one.
     *
     * @return array{count:int,next_at:?Carbon,next_customer:?string,next_branch:?string}
     */
    private function upcoming(Company $company, Carbon $now): array
    {
        $base = Appointment::query()
            ->where('company_id', $company->id)
            ->whereIn('status', self::REAL_STATUSES)
            ->where('start_time', '>', $now);

        $count = (clone $base)->count();

        $next = (clone $base)
            ->with(['customer:id,name', 'branch:id,name_en,name_ar'])
            ->orderBy('start_time')
            ->first();

        return [
            'count'         => $count,
            'next_at'       => $next?->start_time,
            'next_customer' => $next?->displayName(),
            'next_branch'   => $next?->branch?->localizedName(),
        ];
    }

    /**
     * Top 3 services by real-booking count today (by the primary service_id).
     *
     * @return list<array{name:string,count:int}>
     */
    private function topServices(Company $company, Carbon $from, Carbon $to): array
    {
        $rows = Appointment::query()
            ->where('company_id', $company->id)
            ->whereIn('status', self::REAL_STATUSES)
            ->whereBetween('start_time', [$from, $to])
            ->whereNotNull('service_id')
            ->selectRaw('service_id, COUNT(*) as c')
            ->groupBy('service_id')
            ->orderByDesc('c')
            ->limit(3)
            ->pluck('c', 'service_id');

        if ($rows->isEmpty()) {
            return [];
        }

        $names = Service::query()
            ->whereIn('id', $rows->keys())
            ->get(['id', 'name_en', 'name_ar'])
            ->mapWithKeys(fn ($s) => [$s->id => $s->localizedName()]);

        return $rows->map(fn (int $count, int $id) => [
            'name'  => $names[$id] ?? __('Service'),
            'count' => $count,
        ])->values()->all();
    }

    /** Company's dominant service currency symbol, falling back to the default. */
    private function currencySymbol(Company $company): string
    {
        $branchIds = $company->branches()->pluck('id');

        $code = Service::query()
            ->whereIn('branch_id', $branchIds)
            ->whereNotNull('currency')
            ->selectRaw('currency, COUNT(*) as c')
            ->groupBy('currency')
            ->orderByDesc('c')
            ->value('currency')
            ?: config('booksy.default_currency', 'SYP');

        return config("booksy.currencies.$code.symbol", $code);
    }

    /** "?, ?, ?" for an IN() clause of N bound values. */
    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
}
