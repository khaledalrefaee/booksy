<?php

namespace App\Http\Controllers\Owner;

use App\Enums\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ResolvesOwnerCompany;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Platform-owner Appointments — a multi-business, multi-branch management view.
 *
 * The owner may oversee thousands of appointments across every company and
 * branch, so this page is deliberately server-side: filtering, sorting and
 * pagination all happen in SQL (backed by the composite (company_id|branch_id,
 * start_time) indexes) and only one page of rows is ever sent to the browser.
 * The former client-side DataTables layer (which searched only the 15 rows of
 * the current page and warned about column counts) has been removed.
 */
class AppointmentController extends Controller
{
    use ResolvesOwnerCompany;

    /** Default page size. */
    private const PER_PAGE = 20;

    /** Page sizes the owner may pick from the pagination footer. */
    private const PER_PAGE_OPTIONS = [10, 20, 50, 100];

    /** Whitelisted sort columns. */
    private const SORTS = ['start_time', 'created_at', 'total_price'];

    /** Date presets the toolbar chips expose. */
    private const RANGES = ['today', 'tomorrow', 'week', 'custom'];

    public function index(Request $request): View
    {
        $f = $this->parseFilters($request);

        // ── Structural scope (company + branch) drives BOTH the list and the
        //    summary cards, so the cards always describe the slice the owner is
        //    looking at. Status / date / search are list-only refinements. ──
        $scope = Appointment::query()
            ->when($f['company_id'], fn ($q) => $q->where('company_id', $f['company_id']))
            ->when($f['branch_id'], fn ($q) => $q->where('branch_id', $f['branch_id']));

        $stats = $this->summaryStats($scope);

        $appointments = $this->applyRefinements(clone $scope, $f)
            ->with(['branch.company', 'company', 'customer', 'employee', 'service'])
            ->orderBy($f['sort'], $f['dir'])
            ->orderBy('id', 'desc')
            ->paginate($f['per_page'])
            ->withQueryString();

        // ── Selector data. Branches are cheap and power the client-side cascade
        //    (choosing a company narrows the branch list). Employees are only
        //    loaded once a company is chosen, mirroring the real hierarchy
        //    Owner → Company → Branch → Staff and keeping the query bounded. ──
        $companies = Company::query()->orderByLocalizedName()->get(['id', 'name_en', 'name_ar']);
        $branches  = Branch::query()->orderByLocalizedName()->get(['id', 'name_en', 'name_ar', 'company_id']);
        $employees = $f['company_id']
            ? Employee::query()->where('company_id', $f['company_id'])->orderByLocalizedName()->get(['id', 'name_en', 'name_ar', 'company_id'])
            : collect();

        return view('owner.appointments.index', [
            'appointments' => $appointments,
            'stats'        => $stats,
            'companies'    => $companies,
            'branches'     => $branches,
            'employees'    => $employees,
            'statuses'     => AppointmentStatus::cases(),
            'filters'      => $f,
            'perPageOptions' => self::PER_PAGE_OPTIONS,
            'sortField'    => $f['sort'],
            'sortDir'      => $f['dir'],
        ]);
    }

    /**
     * Stream every appointment matching the current filters to a CSV file.
     *
     * Uses lazy chunking so an export of tens of thousands of rows never loads
     * the whole result set into memory. Reuses the exact same filter pipeline as
     * the list, so "Export" always matches what the owner is looking at.
     */
    public function export(Request $request): StreamedResponse
    {
        $f = $this->parseFilters($request);

        $query = $this->applyRefinements(
            Appointment::query()
                ->when($f['company_id'], fn ($q) => $q->where('company_id', $f['company_id']))
                ->when($f['branch_id'], fn ($q) => $q->where('branch_id', $f['branch_id'])),
            $f
        )->with(['branch', 'company', 'customer', 'employee', 'service'])
            ->orderBy($f['sort'], $f['dir'])
            ->orderBy('id', 'desc');

        $filename = 'appointments-' . now()->format('Y-m-d-His') . '.csv';
        $tz       = config('app.timezone');

        return response()->streamDownload(function () use ($query, $tz) {
            $out = fopen('php://output', 'w');
            // BOM so Excel opens UTF-8 (Arabic) correctly.
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('Reference'), __('Company'), __('Branch'), __('Customer'),
                __('Phone'), __('Service'), __('Staff'), __('Date & time'),
                __('Status'), __('Payment'), __('Total'),
            ]);

            $query->chunk(500, function ($rows) use ($out, $tz) {
                foreach ($rows as $a) {
                    fputcsv($out, [
                        $a->reference ?? ('#' . $a->id),
                        $a->company?->localizedName() ?? $a->branch?->company?->localizedName() ?? '',
                        $a->branch?->localizedName() ?? '',
                        $a->displayName(),
                        $a->customer_phone ?? $a->customer?->phone ?? '',
                        $a->service?->localizedName() ?? '',
                        $a->employee?->localizedName() ?? '',
                        $a->start_time?->timezone($tz)->format('Y-m-d H:i') ?? '',
                        $a->status?->label() ?? '',
                        $a->payment_status,
                        number_format((float) $a->total_price, 2),
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Drawer body (layout-less HTML) fetched over AJAX when a row is opened.
     */
    public function detail(Appointment $appointment): View
    {
        $this->authorizeAppointment($appointment);

        $appointment->load([
            'branch.company', 'company', 'customer', 'employee',
            'service.serviceCategory', 'handledBy', 'review',
        ]);

        return view('owner.appointments.partials._drawer', [
            'appointment' => $appointment,
        ]);
    }

    public function show(Appointment $appointment): View
    {
        $this->authorizeAppointment($appointment);

        $appointment->load(['branch.company', 'company', 'customer', 'employee', 'service.serviceCategory', 'handledBy', 'review']);

        return view('owner.appointments.show', [
            'appointment' => $appointment,
        ]);
    }

    // ─────────────────────────── internals ───────────────────────────

    /**
     * Parse and normalise every filter/sort input from the request once, so the
     * list, the summary cards and the CSV export all agree on the same values.
     *
     * @return array<string, mixed>
     */
    private function parseFilters(Request $request): array
    {
        $validated = $request->validate([
            'status'      => ['nullable', 'string'],
            'company_id'  => ['nullable', 'integer', 'exists:companies,id'],
            'branch_id'   => ['nullable', 'integer', 'exists:branches,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'payment'     => ['nullable', 'string', 'max:32'],
            'range'       => ['nullable', 'string', 'in:' . implode(',', self::RANGES)],
            'date_from'   => ['nullable', 'date'],
            'date_to'     => ['nullable', 'date'],
        ]);

        // Guard against legacy 'rejected'/'cancelled' values with no enum case.
        $status = (! empty($validated['status']) && AppointmentStatus::tryFrom($validated['status']))
            ? $validated['status'] : null;

        [$range, $dateFrom, $dateTo] = $this->resolveDateWindow($validated);

        return [
            'q'           => trim((string) $request->input('q', '')),
            'status'      => $status,
            'company_id'  => ! empty($validated['company_id']) ? (int) $validated['company_id'] : null,
            'branch_id'   => ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null,
            'employee_id' => ! empty($validated['employee_id']) ? (int) $validated['employee_id'] : null,
            'payment'     => ! empty($validated['payment']) ? $validated['payment'] : null,
            'range'       => $range,
            'date_from'   => $dateFrom,
            'date_to'     => $dateTo,
            'sort'        => in_array($request->input('sort'), self::SORTS, true) ? $request->input('sort') : 'start_time',
            'dir'         => $request->input('dir') === 'asc' ? 'asc' : 'desc',
            'per_page'    => in_array((int) $request->input('per_page'), self::PER_PAGE_OPTIONS, true) ? (int) $request->input('per_page') : self::PER_PAGE,
        ];
    }

    /**
     * Apply the list-only refinements (status, staff, payment, date, search) on
     * top of an already company/branch-scoped query.
     *
     * @param  Builder<Appointment>  $query
     * @param  array<string, mixed>  $f
     * @return Builder<Appointment>
     */
    private function applyRefinements(Builder $query, array $f): Builder
    {
        return $query
            ->when($f['status'], fn ($q) => $q->where('status', $f['status']))
            ->when($f['employee_id'], fn ($q) => $q->where('employee_id', $f['employee_id']))
            ->when($f['payment'], fn ($q) => $q->where('payment_status', $f['payment']))
            ->when($f['date_from'], fn ($q) => $q->whereDate('start_time', '>=', $f['date_from']))
            ->when($f['date_to'], fn ($q) => $q->whereDate('start_time', '<=', $f['date_to']))
            ->when($f['q'] !== '', function ($q) use ($f) {
                $term = $f['q'];
                $q->where(function ($sub) use ($term) {
                    $sub->where('customer_name', 'like', "%{$term}%")
                        ->orWhere('customer_phone', 'like', "%{$term}%")
                        ->orWhere('reference', 'like', "%{$term}%")
                        ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$term}%"));
                });
            });
    }

    /**
     * Turn the requested preset / custom dates into a concrete [from, to] window.
     *
     * @param  array<string, mixed>  $validated
     * @return array{0: string, 1: ?string, 2: ?string}  [range, dateFrom, dateTo]
     */
    private function resolveDateWindow(array $validated): array
    {
        $range = $validated['range'] ?? null;

        // A raw custom range with no preset is treated as 'custom'.
        if (! $range && (! empty($validated['date_from']) || ! empty($validated['date_to']))) {
            $range = 'custom';
        }

        $today = now()->toDateString();

        return match ($range) {
            'today'    => ['today', $today, $today],
            'tomorrow' => ['tomorrow', now()->addDay()->toDateString(), now()->addDay()->toDateString()],
            'week'     => ['week', $today, now()->addDays(6)->toDateString()],
            'custom'   => ['custom', $validated['date_from'] ?? null, $validated['date_to'] ?? null],
            default    => ['', null, null],
        };
    }

    /**
     * Summary-card counts for the current structural scope, computed with as few
     * indexed queries as possible (one grouped status count + two ranged counts).
     *
     * @param  Builder<Appointment>  $scope
     * @return array<string, int>
     */
    private function summaryStats(Builder $scope): array
    {
        $byStatus = (clone $scope)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $today = (clone $scope)
            ->whereBetween('start_time', [now()->startOfDay(), now()->endOfDay()])
            ->count();

        $upcoming = (clone $scope)
            ->where('start_time', '>', now())
            ->whereIn('status', [
                AppointmentStatus::Pending->value,
                AppointmentStatus::Confirmed->value,
            ])
            ->count();

        return [
            'total'     => (int) $byStatus->sum(),
            'today'     => $today,
            'upcoming'  => $upcoming,
            'pending'   => (int) ($byStatus[AppointmentStatus::Pending->value] ?? 0),
            'completed' => (int) ($byStatus[AppointmentStatus::Completed->value] ?? 0),
            'no_show'   => (int) ($byStatus[AppointmentStatus::NoShow->value] ?? 0),
        ];
    }
}
