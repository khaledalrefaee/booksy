<?php

namespace App\Http\Controllers\Api\Companies;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Company\AppointmentController as WebAppointmentController;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Mobile twin of the web calendar's booking flow (drag & drop on the staff view).
 *
 * The booking rules — blocked time, employee conflicts, rooms/devices, group
 * placement, idempotency, audit log — live in the web controller. This class
 * deliberately DELEGATES to it instead of copying it, so the app and the web
 * panel can never disagree about whether a slot is free. All that happens here
 * is (1) authenticating the bearer-token company as the `company` guard user
 * the web code expects, and (2) re-wrapping its {ok, …} JSON in the unified
 * {status, message, data} envelope.
 *
 * Flow for the app:
 *   1. GET   appointments/staff-events  → columns (employees) + the day's bookings + closed zones
 *   2. GET   appointments/branch-data   → services & employees for the booking sheet
 *   3. GET   appointments/customers     → optional customer picker
 *   4. POST  appointments               → tap/drag an empty slot → book
 *   5. PATCH appointments/{id}/reschedule → drag to move / resize / change employee
 */
class AppointmentController extends ApiController
{
    public function __construct(
        private WebAppointmentController $web,
    ) {}

    /** GET — staff columns + closed zones + the day's appointments (the drag & drop board). */
    public function staffEvents(Request $request): JsonResponse
    {
        $request->validate([
            'date'      => ['nullable', 'date_format:Y-m-d'],
            'branch_id' => ['nullable', 'integer'],
        ]);

        $this->actAsCompany($request);
        $this->assertBranch($request, $request->input('branch_id'));

        return $this->wrap(fn () => $this->web->staffEvents($request), strip: ['showUrl']);
    }

    /**
     * GET — the calendar for a date range (day / week / month), same data as the
     * web calendar. `start` and `end` are dates (YYYY-MM-DD, inclusive); for a
     * day view send the same date twice. `aggregate=1` returns per-day counts
     * (month view) instead of individual appointments.
     */
    public function calendar(Request $request): JsonResponse
    {
        $request->validate([
            'start'     => ['required', 'date_format:Y-m-d'],
            'end'       => ['required', 'date_format:Y-m-d', 'after_or_equal:start'],
            'branch_id' => ['nullable', 'string'],
            'statuses'  => ['nullable', 'string'],
            'aggregate' => ['nullable', 'boolean'],
        ]);

        $this->actAsCompany($request);
        $this->dropEmptyStatuses($request);

        // The web filter is `start_time <= end`, so a bare date would stop at 00:00.
        $request->merge([
            'start' => $request->input('start') . ' 00:00:00',
            'end'   => $request->input('end') . ' 23:59:59',
        ]);

        $events = $this->web->calendarEvents($request)->getData(true);

        $out = [
            'start'        => substr($request->input('start'), 0, 10),
            'end'          => substr($request->input('end'), 0, 10),
            'appointments' => [],
            'blocked'      => [],
            'closed'       => [],
            'days'         => [],
        ];

        foreach ($events as $e) {
            $type = $e['extendedProps']['type'] ?? null;

            match ($type) {
                'appointment' => $out['appointments'][] = $this->presentAppointment($e),
                'blocked'     => $out['blocked'][] = [
                    'id'       => $e['extendedProps']['blockId'],
                    'date'     => substr($e['start'], 0, 10),
                    'start'    => $e['start'],
                    'end'      => $e['end'],
                    'reason'   => $e['extendedProps']['reason'],
                    'employee' => $e['extendedProps']['employee'],
                    'branch'   => $e['extendedProps']['branch'],
                ],
                'closed', 'outside-hours' => $out['closed'][] = [
                    'type'  => $type,
                    'start' => $e['start'],
                    'end'   => $e['end'],
                ],
                'day-count' => $out['days'][] = [
                    'date'      => $e['extendedProps']['day'],
                    'count'     => $e['extendedProps']['count'],
                    'by_status' => $e['extendedProps']['byStatus'],
                ],
                default => null,
            };
        }

        // Month view: per-day counts, plus only the days the branch is closed all
        // day (e.g. Sundays) — the per-day opening-hours stripes are noise there.
        if ($request->boolean('aggregate')) {
            $out['closed'] = array_values(array_filter($out['closed'], fn ($c) => $c['type'] === 'closed'));
        }

        return $this->success($out);
    }

    /**
     * GET — appointments list, newest/closest first, searchable and paginated
     * (the web list tab). Filters: q, statuses, branch_id, sort
     * (closest|farthest|newest|price-high|price-low), per_page, page.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'q'        => ['nullable', 'string', 'max:100'],
            'sort'     => ['nullable', 'in:closest,farthest,newest,price-high,price-low'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page'     => ['nullable', 'integer', 'min:1'],
        ]);

        $this->actAsCompany($request);
        $this->dropEmptyStatuses($request);

        $body = $this->web->listData($request)->getData(true);

        return $this->success([
            'appointments' => array_map(fn ($e) => $this->presentAppointment($e), $body['data']),
            'meta'         => $body['meta'],
        ]);
    }

    /**
     * GET — the bookable services of a branch, each with the employees who
     * perform it: pick a service, then offer exactly those employees.
     */
    public function services(Request $request): JsonResponse
    {
        $request->validate(['branch_id' => ['required', 'integer']]);

        $this->actAsCompany($request);

        $body = $this->web->branchData($request)->getData(true);

        $services = array_map(function ($svc) use ($body) {
            $svc['employees'] = array_values(array_map(
                fn ($e) => ['id' => $e['id'], 'name' => $e['name']],
                array_filter($body['employees'], fn ($e) => in_array($svc['id'], $e['service_ids'], true)),
            ));

            return $svc;
        }, $body['services']);

        return $this->success(['services' => $services]);
    }

    /** GET — services (with price/duration) and bookable employees of one branch. */
    public function branchData(Request $request): JsonResponse
    {
        $request->validate(['branch_id' => ['required', 'integer']]);

        $this->actAsCompany($request);

        return $this->wrap(fn () => $this->web->branchData($request));
    }

    /**
     * GET — the company's OWN customers (name/phone search, newest visit first).
     * Only people who booked at one of its branches, or that it added itself.
     * Params: q, limit (1..50, default 20).
     */
    public function customers(Request $request): JsonResponse
    {
        $request->validate([
            'q'     => ['nullable', 'string', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $company   = $request->attributes->get('company');
        $branchIds = $company->branches()->pluck('id');
        $q         = trim((string) $request->input('q', ''));

        $customers = Customer::query()
            ->ofBranches($branchIds)
            ->when($q !== '', fn ($w) => $w->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$q}%")
                ->orWhere('phone', 'like', "%{$q}%")))
            ->withCount(['appointments as visits_count' => fn ($a) => $a
                ->whereIn('branch_id', $branchIds)
                ->where('status', \App\Enums\AppointmentStatus::Completed->value)])
            ->withMax(['appointments as last_visit' => fn ($a) => $a->whereIn('branch_id', $branchIds)], 'start_time')
            ->orderByDesc('last_visit')
            ->orderBy('name')
            ->limit((int) $request->input('limit', 20))
            ->get(['id', 'name', 'phone', 'tag']);

        return $this->success(['customers' => $customers->map(function (Customer $c) {
            $tier = $c->tier();

            return [
                'id'         => $c->id,
                'name'       => $c->name,
                'phone'      => $c->phone,
                'visits'     => (int) $c->visits_count,
                'last_visit' => $c->last_visit ? substr((string) $c->last_visit, 0, 10) : null,
                'tier'       => ['value' => $tier->value, 'label' => $tier->label(), 'color' => $tier->color()],
            ];
        })->values()]);
    }

    /**
     * POST — book one appointment (one or more services) at a picked slot.
     *
     * Simple form (recommended):
     *   { branch_id, start_time, customer_id | customer_name + customer_phone,
     *     services: [ { service_id, employee_id?, price?, duration? } ] }
     * The older parallel arrays (service_ids / prices / durations / employee_ids)
     * still work; `services` is just turned into them.
     */
    public function store(Request $request): JsonResponse
    {
        if ($request->has('services')) {
            $request->validate([
                'services'               => ['required', 'array', 'min:1'],
                'services.*.service_id'  => ['required', 'integer'],
                'services.*.employee_id' => ['nullable', 'integer'],
                'services.*.price'       => ['nullable', 'numeric', 'min:0'],
                'services.*.duration'    => ['nullable', 'integer', 'min:5', 'max:1440'],
            ]);

            $rows = array_values($request->input('services'));
            $request->merge([
                'service_ids'  => array_column($rows, 'service_id'),
                'employee_ids' => array_map(fn ($r) => $r['employee_id'] ?? null, $rows),
                'prices'       => array_map(fn ($r) => $r['price'] ?? null, $rows),
                'durations'    => array_map(fn ($r) => $r['duration'] ?? null, $rows),
            ]);
        }

        $this->actAsCompany($request);

        return $this->wrap(
            fn () => $this->web->quickStore($request),
            __('Appointment booked.'),
            strip: ['showUrl'],
            status: 201,
        );
    }

    /** POST — book several guests together as one party. */
    public function storeGroup(Request $request): JsonResponse
    {
        $this->actAsCompany($request);

        return $this->wrap(
            fn () => $this->web->quickGroupStore($request),
            __('Group booking created.'),
            status: 201,
        );
    }

    /** PATCH — drag & drop: move to another time and/or employee, or resize via `duration`. */
    public function reschedule(Request $request, Appointment $appointment): JsonResponse
    {
        $this->actAsCompany($request);

        return $this->wrap(
            fn () => $this->web->reschedule($request, $appointment),
            __('Appointment updated.'),
        );
    }

    // ── internals ─────────────────────────────────────────────────────────

    /** The web filters treat a present-but-empty `statuses` as "none selected" — for the app, empty means "all". */
    private function dropEmptyStatuses(Request $request): void
    {
        if (! $request->filled('statuses')) {
            $request->query->remove('statuses');
            $request->request->remove('statuses');
        }
    }

    /** The web booking code reads the company from the `company` guard — hand it ours. */
    private function actAsCompany(Request $request): void
    {
        Auth::guard('company')->setUser($request->attributes->get('company'));
    }

    private function assertBranch(Request $request, mixed $branchId): void
    {
        if ($branchId) {
            abort_unless($request->attributes->get('company')->branches()->where('id', $branchId)->exists(), 403);
        }
    }

    /**
     * Run a web action and re-wrap its JSON in the API envelope.
     *
     * The web code reports business refusals ("employee is busy", "time is
     * blocked") as a 422 {ok:false, message}, sometimes by throwing
     * HttpResponseException — both come out here as a normal API error.
     *
     * @param  list<string>  $strip  keys that only make sense on the web (page URLs)
     */
    private function wrap(\Closure $action, string $message = '', array $strip = [], int $status = 200): JsonResponse
    {
        try {
            $response = $action();
        } catch (HttpResponseException $e) {
            $response = $e->getResponse();
        }

        $body = $response instanceof JsonResponse ? $response->getData(true) : [];

        if ($response->getStatusCode() >= 400 || ($body['ok'] ?? true) === false) {
            return $this->error(
                $body['message'] ?? __('This time is not available.'),
                $response->getStatusCode() >= 400 ? $response->getStatusCode() : 422,
                null,
                isset($body['code']) ? ['code' => $body['code']] : null,
            );
        }

        unset($body['ok']);
        $body = $this->stripKeys($body, $strip);

        return $this->success($body, $message, $status);
    }

    /** One FullCalendar-style appointment event → the flat shape the app uses. */
    private function presentAppointment(array $e): array
    {
        $p = $e['extendedProps'];

        return [
            'id'           => $e['id'],
            'date'         => substr($e['start'], 0, 10),
            'start'        => $e['start'],
            'end'          => $e['end'],
            'start_time'   => substr($e['start'], 11, 5),
            'end_time'     => $e['end'] ? substr($e['end'], 11, 5) : null,
            'status'       => $p['status'],
            'color'        => $e['backgroundColor'],
            'customer'     => $p['customer'],
            'customer_phone' => $p['customerPhone'],
            'service'      => $p['service'],
            'employee_id'  => $p['employeeId'],
            'employee'     => $p['employeeId'] ? $p['employee'] : null,
            'employee_image' => $p['employeeImage'],
            'branch_id'    => $p['branchId'],
            'branch'       => $p['branch'],
            'resource'     => $p['resource'],
            'price'        => $p['price'],
            'currency'     => $p['currency'],
            'is_group'     => $p['group'],
        ];
    }

    /** Remove web-only keys at any depth. */
    private function stripKeys(array $data, array $keys): array
    {
        foreach ($data as $k => $v) {
            if (in_array($k, $keys, true)) {
                unset($data[$k]);
            } elseif (is_array($v)) {
                $data[$k] = $this->stripKeys($v, $keys);
            }
        }

        return $data;
    }
}
