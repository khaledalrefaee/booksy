<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Customer self-service area (/account/*). Guarded by the `customer.auth`
 * middleware, which resolves the phone+OTP session into a Customer and shares
 * it as `current_customer`.
 */
class CustomerAccountController extends Controller
{
    public function __construct(private readonly TransitionAppointment $transition)
    {
    }

    /** The signed-in customer (guaranteed present by middleware). */
    private function customer(): Customer
    {
        return app('current_customer');
    }

    /** GET /account/appointments — upcoming + past, split for the tabbed UI. */
    public function appointments(Request $request)
    {
        $isAr     = app()->getLocale() === 'ar';
        $customer = $this->customer();

        $appointments = $customer->appointments()
            ->with(['branch.images', 'branch.company', 'service', 'employee'])
            ->orderByDesc('start_time')
            ->get();

        $cancelled = AppointmentStatus::cancelled();

        // Upcoming = still active and in the future. Everything else is history.
        $upcoming = $appointments
            ->filter(fn ($a) => $a->start_time->isFuture()
                && ! in_array($a->status, $cancelled, true)
                && $a->status !== AppointmentStatus::NoShow)
            ->sortBy('start_time')
            ->values();

        $past = $appointments
            ->reject(fn ($a) => $upcoming->contains('id', $a->id))
            ->values();

        // Collapse a multi-service / multi-guest visit (one booking_group_id) into
        // ONE card — the customer made one booking, so they should see one entry.
        $upcomingVisits = $this->toVisits($upcoming, $isAr, true);
        $pastVisits     = $this->toVisits($past, $isAr, false);

        return view('front.account.appointments', compact('upcomingVisits', 'pastVisits', 'isAr'));
    }

    /**
     * Group appointment rows into visits by booking_group_id (a lone booking is
     * its own visit), returning a display-ready summary per visit.
     */
    private function toVisits($rows, bool $isAr, bool $upcoming)
    {
        return $rows
            ->groupBy(fn ($a) => $a->booking_group_id ?: 's' . $a->id)
            ->map(function ($g) use ($isAr) {
                $g       = $g->sortBy('start_time')->values();
                $primary = $g->first();
                $names   = $g->map(fn ($a) => $a->service
                        ? ($isAr ? ($a->service->name_ar ?? $a->service->name_en) : ($a->service->name_en ?? $a->service->name_ar))
                        : '')->filter()->values();
                // Extra guests carry a label; the number of distinct labels + the
                // account holder is how many people the visit is for.
                $guests = $g->pluck('customer_name')->filter()->unique()->count();

                return (object) [
                    'primary'       => $primary,
                    'rows'          => $g,
                    'service_count' => $g->count(),
                    'service_names' => $names,
                    'total'         => (float) $g->sum('total_price'),
                    'guest_count'   => $guests,
                    'status'        => self::statusView($primary, $isAr),
                ];
            })
            ->sortBy(fn ($v) => $v->primary->start_time, SORT_REGULAR, ! $upcoming)
            ->values();
    }

    /** GET /account/appointments/{appointment} — one appointment's full detail. */
    public function show(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);

        $appointment->load(['branch.images', 'branch.company.category', 'service', 'employee', 'review', 'transitions']);
        $isAr = app()->getLocale() === 'ar';

        // Every row of this visit (a grouped multi-service / multi-guest booking),
        // so the detail page shows the whole booking, not just one service.
        $visitRows = $appointment->booking_group_id
            ? Appointment::where('booking_group_id', $appointment->booking_group_id)
                ->with(['service', 'employee'])->orderBy('start_time')->get()
            : collect([$appointment]);
        $visitTotal = (float) $visitRows->sum('total_price');

        $canCancel = in_array($appointment->status, [
            AppointmentStatus::Pending,
            AppointmentStatus::Confirmed,
        ], true) && $appointment->start_time->isFuture();

        // A completed visit with no review yet can be rated.
        $canReview = $appointment->status === AppointmentStatus::Completed
            && $appointment->review === null;

        // Reason recorded on the latest cancellation, surfaced in the booking's history.
        $cancelReason = null;
        if (in_array($appointment->status, AppointmentStatus::cancelled(), true)) {
            $cancelReason = $appointment->transitions
                ->whereIn('to_status', AppointmentStatus::cancelled())
                ->last()?->reason
                ?: $appointment->rejection_reason;
        }

        $cancelReasons = $this->cancelReasons();

        // A future pending/confirmed booking can be moved to another time.
        $canReschedule = $canCancel;

        // Cancellation policy the customer should see up-front: free until N hours
        // before the start; cancelling after that is a "late" cancellation.
        $policy            = $appointment->company?->effectiveBookingPolicy($appointment->branch);
        $cancelWindowHours = (int) ($policy->cancellation_window_hours ?? 24);
        $freeUntil         = $appointment->start_time->copy()->subHours($cancelWindowHours);
        $isLateCancel      = now()->greaterThan($freeUntil);

        // Human-readable timeline for the customer + support, newest last.
        $timeline = $appointment->transitions
            ->sortBy('created_at')
            ->map(fn ($t) => [
                'at'     => $t->created_at,
                'label'  => $this->timelineLabel($t, $isAr),
                'reason' => $t->reason,
                'by'     => $t->actor_name,
            ])->values();

        return view('front.account.appointment-show', compact(
            'appointment', 'isAr', 'canCancel', 'canReview', 'canReschedule',
            'cancelReason', 'cancelReasons', 'timeline', 'visitRows', 'visitTotal',
            'cancelWindowHours', 'freeUntil', 'isLateCancel',
        ));
    }

    /** Friendly one-line description of a transition for the customer timeline. */
    private function timelineLabel(\App\Models\AppointmentTransition $t, bool $isAr): string
    {
        if (($t->meta['event'] ?? null) === 'rescheduled') {
            return $isAr ? 'أُعيدت جدولة الموعد' : 'Appointment rescheduled';
        }
        if ($t->from_status === null) {
            return $isAr ? 'تم إنشاء الحجز' : 'Booking created';
        }
        return match ($t->to_status) {
            AppointmentStatus::Confirmed           => $isAr ? 'أكّد المركز الموعد' : 'Confirmed by venue',
            AppointmentStatus::Arrived             => $isAr ? 'تم تسجيل الوصول' : 'Checked in',
            AppointmentStatus::InProgress          => $isAr ? 'بدأت الخدمة' : 'Service started',
            AppointmentStatus::Completed           => $isAr ? 'اكتملت الزيارة' : 'Visit completed',
            AppointmentStatus::NoShow              => $isAr ? 'لم يتم الحضور' : 'Marked no-show',
            AppointmentStatus::CancelledByCustomer => $isAr ? 'ألغى العميل الموعد' : 'Cancelled by customer',
            AppointmentStatus::CancelledBySalon    => ($t->from_status === AppointmentStatus::Pending)
                                                        ? ($isAr ? 'رفض المركز الطلب' : 'Rejected by venue')
                                                        : ($isAr ? 'ألغى المركز الموعد' : 'Cancelled by venue'),
            default => $t->to_status?->label() ?? '—',
        };
    }

    /**
     * GET /account/appointments/{appointment}/status — lightweight JSON the page
     * polls so a confirmation/cancellation from the venue shows without a refresh.
     */
    public function status(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);
        $isAr = app()->getLocale() === 'ar';
        $view = self::statusView($appointment, $isAr);

        return response()->json($view + [
            'cancelled' => in_array($appointment->status, AppointmentStatus::cancelled(), true),
            // Lets the page catch a venue-side reschedule (time moved) live, not
            // just a status change.
            'start'         => $appointment->start_time->toDateTimeString(),
            'start_display' => $appointment->start_time->translatedFormat('l') . ' · '
                             . $appointment->start_time->format('d/m — g:i A'),
        ]);
    }

    /**
     * Customer-facing status label + colour. A confirmed future booking reads as
     * "Coming / قادم" (Fresha-style), which is friendlier than the raw state.
     *
     * @return array{value:string,label:string,color:string}
     */
    public static function statusView(Appointment $appointment, bool $isAr): array
    {
        $status = $appointment->status;
        $future = $appointment->start_time->isFuture();

        // A salon-side cancellation that happened straight out of "pending" is a
        // rejection of the request — a distinct thing from cancelling a confirmed
        // booking, so name it that way for the customer.
        $isRejection = $status === AppointmentStatus::CancelledBySalon
            && $appointment->status_previous === AppointmentStatus::Pending->value;

        $label = match (true) {
            $isRejection                                        => $isAr ? 'مرفوض' : 'Rejected',
            $status === AppointmentStatus::Confirmed && $future => $isAr ? 'قادم' : 'Coming',
            $status === AppointmentStatus::Pending   && $future => $isAr ? 'بانتظار التأكيد' : 'Pending confirmation',
            default => $status->label(),
        };

        return [
            'value' => $status->value,
            'label' => $label,
            'color' => $status->color(),
        ];
    }

    /** POST /account/appointments/{appointment}/review — rate a completed visit. */
    public function storeReview(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);
        $isAr = app()->getLocale() === 'ar';

        if ($appointment->status !== AppointmentStatus::Completed) {
            return back()->with('account_error', $isAr ? 'يمكن تقييم المواعيد المكتملة فقط.' : 'Only completed visits can be reviewed.');
        }
        if ($appointment->review()->exists()) {
            return back()->with('account_error', $isAr ? 'لقد قيّمت هذا الموعد مسبقاً.' : 'You already reviewed this appointment.');
        }

        $data = $request->validate([
            'rating'  => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        \App\Models\Review::create([
            'branch_id'      => $appointment->branch_id,
            'appointment_id' => $appointment->id,
            'customer_id'    => $this->customer()->id,
            'rating'         => $data['rating'],
            'comment'        => $data['comment'] ?? null,
            'is_hidden'      => false,
        ]);

        return redirect()
            ->route('account.appointment', $appointment)
            ->with('account_success', $isAr ? 'شكراً لك! تم نشر تقييمك.' : 'Thanks! Your review has been posted.');
    }

    /**
     * GET /account/appointments/{appointment}/calendar — an .ics event for the
     * whole visit. Served INLINE (not attachment) so iOS Safari opens the native
     * "Add to Calendar" sheet instead of dropping the file into Files.
     */
    public function calendar(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);
        $appointment->load(['branch', 'service']);

        $isAr  = app()->getLocale() === 'ar';
        $venue = $isAr ? ($appointment->branch->name_ar ?? $appointment->branch->name_en) : ($appointment->branch->name_en ?? $appointment->branch->name_ar);

        // Whole visit → one event spanning first start to last end, all services listed.
        $rows = $appointment->booking_group_id
            ? Appointment::where('booking_group_id', $appointment->booking_group_id)->with('service')->orderBy('start_time')->get()
            : collect([$appointment]);

        $svcName = fn ($a) => $a->service
            ? ($isAr ? ($a->service->name_ar ?? $a->service->name_en) : ($a->service->name_en ?? $a->service->name_ar))
            : ($isAr ? 'موعد' : 'Appointment');

        $start = $rows->first()->start_time;
        $end   = $rows->last()->end_time ?? $rows->last()->start_time->copy()->addMinutes(30);

        $summary = $rows->count() > 1
            ? ($isAr ? $rows->count() . ' خدمات — ' . $venue : $rows->count() . ' services — ' . $venue)
            : $svcName($rows->first()) . ' — ' . $venue;

        // iCalendar escapes commas, semicolons, backslashes; \n stays literal for line breaks.
        $esc  = fn ($s) => str_replace("\n", '\n', addcslashes((string) $s, ",;\\"));
        $fmt  = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');

        $descLines = [];
        foreach ($rows as $r) {
            $descLines[] = $r->start_time->format('g:i A') . ' • ' . $svcName($r);
        }
        if ($appointment->reference) {
            $descLines[] = ($isAr ? 'رقم الحجز: ' : 'Booking ref: ') . $appointment->reference;
        }
        $descLines[] = $isAr ? 'عبر غلوريز' : 'via GlowRez';

        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//GlowRez//Appointments//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:glowrez-visit-' . ($appointment->booking_group_id ?: $appointment->id) . '@' . parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $fmt($start),
            'DTEND:'   . $fmt($end),
            'SUMMARY:' . $esc($summary),
            'LOCATION:' . $esc($appointment->branch->address ?? $venue),
            'DESCRIPTION:' . $esc(implode("\n", $descLines)),
            'STATUS:CONFIRMED',
            'BEGIN:VALARM', 'ACTION:DISPLAY', 'DESCRIPTION:' . $esc($summary), 'TRIGGER:-PT1H', 'END:VALARM',
            'END:VEVENT', 'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines), 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'inline; filename="glowrez-appointment.ics"',
        ]);
    }

    /** POST /account/appointments/{appointment}/cancel — customer self-cancel. */
    public function cancel(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);

        $reasons = $this->cancelReasons();
        $data = $request->validate([
            'reason' => ['nullable', Rule::in(array_keys($reasons))],
            'note'   => ['nullable', 'string', 'max:400'],
        ]);

        // Build a human-readable reason (preset label + optional free note), the
        // same shape the reminder-link cancel records, so the booking history
        // reads consistently wherever the cancellation came from.
        $reason = $data['reason'] ? $reasons[$data['reason']] : __('Cancelled by customer');
        if (!empty($data['note'])) {
            $reason .= ' — ' . trim($data['note']);
        }

        // Cancel the whole visit (a grouped multi-service / multi-guest booking).
        $rows = $appointment->booking_group_id
            ? Appointment::where('booking_group_id', $appointment->booking_group_id)->get()
            : collect([$appointment]);

        $ok = false;
        foreach ($rows as $row) {
            if ($this->transition->attempt(
                $row,
                AppointmentStatus::CancelledByCustomer,
                TransitionActor::Customer,
                [
                    'actorId'   => $this->customer()->id,
                    'actorName' => $this->customer()->name,
                    'reason'    => $reason,
                    'meta'      => ['source' => 'customer_account', 'reason' => $reason],
                ],
            )) {
                $ok = true;
            }
        }

        $isAr = app()->getLocale() === 'ar';

        return redirect()
            ->route('account.appointment', $appointment)
            ->with($ok ? 'account_success' : 'account_error',
                $ok
                    ? ($isAr ? 'تم إلغاء موعدك. شكراً لإعلامنا 🙏' : 'Your appointment has been cancelled. Thanks for letting us know 🙏')
                    : ($isAr ? 'تعذّر إلغاء هذا الموعد.' : 'This appointment can no longer be cancelled.'));
    }

    /** Preset cancellation reasons (value => translated label) — shared UX. */
    private function cancelReasons(): array
    {
        return [
            'emergency' => __('Emergency came up'),
            'conflict'  => __('Schedule conflict'),
            'changed'   => __('Changed my mind'),
            'found'     => __('Found another time'),
            'other'     => __('Other reason'),
        ];
    }

    /**
     * POST /account/appointments/{appointment}/reschedule — move the whole visit
     * to a new time, re-checking availability under a lock. One notification.
     */
    public function reschedule(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);
        $isAr = app()->getLocale() === 'ar';

        $request->validate(['start_time' => ['required', 'date']]);
        $oldStart = $appointment->start_time->toDateTimeString();
        $newStart = \Illuminate\Support\Carbon::parse($request->input('start_time'));

        $result = app(\App\Actions\Appointment\RescheduleAppointment::class)(
            $appointment,
            $newStart,
            'customer',
            ['actorId' => $this->customer()->id, 'actorName' => $this->customer()->name, 'source' => 'customer_account'],
        );

        if (! ($result['ok'] ?? false)) {
            return redirect()->route('account.appointment', $appointment)
                ->with('account_error', $result['message'] ?? ($isAr ? 'تعذّرت إعادة الجدولة.' : 'Could not reschedule.'));
        }

        $fresh = $result['appointment'];
        try { event(new \App\Events\AppointmentRescheduled($fresh, $oldStart)); } catch (\Throwable $e) {}
        dispatch(function () use ($fresh, $oldStart) {
            app(\App\Services\WhatsappService::class)->sendAppointmentRescheduled($fresh->fresh(), $oldStart);
        })->afterResponse();

        return redirect()->route('account.appointment', $appointment)
            ->with('account_success', $isAr ? 'تم تغيير موعدك بنجاح 🔄' : 'Your appointment has been rescheduled 🔄');
    }

    /** GET /account/profile — profile + settings (edit, language, delete). */
    public function profile(Request $request)
    {
        $isAr     = app()->getLocale() === 'ar';
        $customer = $this->customer();

        // Membership: reuse the existing tier engine + points balance.
        $visits = $customer->appointments()->where('status', AppointmentStatus::Completed->value)->count();
        $customer->setAttribute('visits_count', $visits);
        $tier    = $customer->tier();
        $points  = (int) $customer->loyalty_points;
        $loyalAt = \App\Enums\CustomerTier::LOYAL_AT;

        return view('front.account.profile', compact('customer', 'isAr', 'tier', 'points', 'visits', 'loyalAt'));
    }

    /** POST /account/profile — save name/age. */
    public function updateProfile(Request $request)
    {
        $customer = $this->customer();
        $isAr     = app()->getLocale() === 'ar';

        $data = $request->validate([
            'name' => 'required|string|max:80',
            'age'  => 'nullable|integer|min:10|max:100',
        ]);

        $customer->update(['name' => $data['name'], 'age' => $data['age'] ?? null]);

        return back()->with('account_success', $isAr ? 'تم حفظ بياناتك.' : 'Your details were saved.');
    }

    /**
     * POST /account/delete — customer-initiated account deletion.
     * We anonymise rather than hard-delete: the venue keeps its (now PII-free)
     * visit records for its own accounting, while the person's identity and
     * ability to log in are removed and their phone is freed for re-use.
     */
    public function deleteAccount(Request $request)
    {
        $customer = $this->customer();
        $isAr     = app()->getLocale() === 'ar';

        $customer->favoriteBranches()->detach();
        \App\Models\OtpCode::where('phone', $customer->phone)->delete();

        $customer->update([
            'name'              => '',
            'phone'             => 'deleted-' . $customer->id,
            'age'               => null,
            'avatar'            => null,
            'notes'             => null,
            'phone_verified_at' => null,
        ]);

        session()->forget('customer_id');

        return redirect()->route('front.index');
    }

    /** GET /account/favorites — the customer's saved venues as full venue cards. */
    public function favorites(Request $request)
    {
        $isAr = app()->getLocale() === 'ar';

        $branches = $this->customer()->favoriteBranches()
            ->with(['company.category', 'images', 'governorate', 'area',
                    'services' => fn ($q) => $q->where('is_active', true)])
            ->withCount(['reviews', 'appointments'])
            ->withAvg('reviews', 'rating')
            ->whereHas('company', fn ($q) => $q->where('status', 'active'))
            ->get();

        // Same card shape home/venues use, so the venue-card partial renders identically.
        $cards    = $branches->map(fn ($b) => FrontController::branchToCard($b, $isAr));
        $currency = $isAr ? 'ل.س' : 'SYP';

        return view('front.account.favorites', compact('cards', 'currency', 'isAr'));
    }

    /** 404 (not 403) so we never confirm the existence of another user's booking. */
    private function authorizeOwnership(Appointment $appointment): void
    {
        abort_unless($appointment->customer_id === $this->customer()->id, 404);
    }
}
