<?php

namespace App\Http\Controllers\Api\Companies;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Company\AppointmentController as WebAppointmentController;
use App\Http\Controllers\Company\CustomerController as WebCustomerController;
use App\Models\Appointment;
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
        private WebCustomerController $customers,
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

    /** GET — services (with price/duration) and bookable employees of one branch. */
    public function branchData(Request $request): JsonResponse
    {
        $request->validate(['branch_id' => ['required', 'integer']]);

        $this->actAsCompany($request);

        return $this->wrap(fn () => $this->web->branchData($request));
    }

    /** GET — customer picker (name/phone search, max 15). */
    public function customers(Request $request): JsonResponse
    {
        $this->actAsCompany($request);

        $response = $this->customers->searchJson($request);

        return $this->success(['customers' => $response->getData(true)]);
    }

    /** POST — book one appointment (one or more services) at a picked slot. */
    public function store(Request $request): JsonResponse
    {
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
