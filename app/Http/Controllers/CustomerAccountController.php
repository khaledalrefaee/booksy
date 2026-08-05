<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\TransitionAppointment;
use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use App\Models\Appointment;
use App\Models\Customer;
use Illuminate\Http\Request;

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

        return view('front.account.appointments', compact('upcoming', 'past', 'isAr'));
    }

    /** GET /account/appointments/{appointment} — one appointment's full detail. */
    public function show(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);

        $appointment->load(['branch.images', 'branch.company.category', 'service', 'employee', 'review']);
        $isAr = app()->getLocale() === 'ar';

        $canCancel = in_array($appointment->status, [
            AppointmentStatus::Pending,
            AppointmentStatus::Confirmed,
        ], true) && $appointment->start_time->isFuture();

        // A completed visit with no review yet can be rated.
        $canReview = $appointment->status === AppointmentStatus::Completed
            && $appointment->review === null;

        return view('front.account.appointment-show', compact('appointment', 'isAr', 'canCancel', 'canReview'));
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

    /** GET /account/appointments/{appointment}/calendar — download an .ics file. */
    public function calendar(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);
        $appointment->load(['branch', 'service']);

        $isAr  = app()->getLocale() === 'ar';
        $venue = $isAr ? ($appointment->branch->name_ar ?? $appointment->branch->name_en) : ($appointment->branch->name_en ?? $appointment->branch->name_ar);
        $svc   = $appointment->service
            ? ($isAr ? ($appointment->service->name_ar ?? $appointment->service->name_en) : ($appointment->service->name_en ?? $appointment->service->name_ar))
            : ($isAr ? 'موعد' : 'Appointment');

        $esc = fn ($s) => addcslashes((string) $s, ",;\\") ;
        $fmt = fn ($d) => $d->copy()->utc()->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR', 'VERSION:2.0', 'PRODID:-//Booksy//Appointments//EN', 'CALSCALE:GREGORIAN', 'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            'UID:booksy-appt-' . $appointment->id . '@' . parse_url(config('app.url'), PHP_URL_HOST),
            'DTSTAMP:' . $fmt(now()),
            'DTSTART:' . $fmt($appointment->start_time),
            'DTEND:'   . $fmt($appointment->end_time),
            'SUMMARY:' . $esc($svc . ' — ' . $venue),
            'LOCATION:' . $esc($appointment->branch->address),
            'DESCRIPTION:' . $esc(($isAr ? 'موعدك عبر بوكسي' : 'Your booking via Booksy')),
            'END:VEVENT', 'END:VCALENDAR',
        ];

        return response(implode("\r\n", $lines), 200, [
            'Content-Type'        => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="booksy-appointment-' . $appointment->id . '.ics"',
        ]);
    }

    /** POST /account/appointments/{appointment}/cancel — customer self-cancel. */
    public function cancel(Request $request, Appointment $appointment)
    {
        $this->authorizeOwnership($appointment);

        $ok = $this->transition->attempt(
            $appointment,
            AppointmentStatus::CancelledByCustomer,
            TransitionActor::Customer,
            [
                'actorId'   => $this->customer()->id,
                'actorName' => $this->customer()->name,
                'reason'    => $request->string('reason')->toString() ?: __('Cancelled by customer'),
                'meta'      => ['source' => 'customer_account'],
            ],
        );

        $isAr = app()->getLocale() === 'ar';

        return redirect()
            ->route('account.appointment', $appointment)
            ->with($ok ? 'account_success' : 'account_error',
                $ok
                    ? ($isAr ? 'تم إلغاء الموعد.' : 'Your appointment has been cancelled.')
                    : ($isAr ? 'تعذّر إلغاء هذا الموعد.' : 'This appointment can no longer be cancelled.'));
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
