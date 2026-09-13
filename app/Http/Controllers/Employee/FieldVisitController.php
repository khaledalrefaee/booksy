<?php

namespace App\Http\Controllers\Employee;

use App\Http\Controllers\Controller;
use App\Models\FieldVisit;
use App\Services\FieldSales\VisitVerification;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Field-sales rep area (guard: owner). A rep only ever sees and edits their own
 * visits — every query is scoped by owner_id. The presence signals (GPS, dwell,
 * photo) are captured silently and turned into a manager-only confidence score
 * by {@see VisitVerification}; the rep never sees any of that.
 */
class FieldVisitController extends Controller
{
    public function __construct(private VisitVerification $verifier) {}

    private function ownerId(): int
    {
        return (int) Auth::guard('owner')->id();
    }

    /** A visit must belong to the current rep, or it doesn't exist for them. */
    private function ownVisit(FieldVisit $visit): void
    {
        abort_unless($visit->owner_id === $this->ownerId(), 404);
    }

    public function home(): View
    {
        $ownerId = $this->ownerId();
        $today   = FieldVisit::forOwner($ownerId)->whereDate('visited_at', today())->count();
        $week    = FieldVisit::forOwner($ownerId)->where('visited_at', '>=', now()->startOfWeek())->count();
        $followUps = FieldVisit::forOwner($ownerId)
            ->whereNotNull('follow_up_date')
            ->whereDate('follow_up_date', '<=', today())
            ->orderBy('follow_up_date')
            ->limit(5)->get();

        $recent = FieldVisit::forOwner($ownerId)->orderByDesc('visited_at')->limit(6)->get();

        return view('employee.home', [
            'owner'     => Auth::guard('owner')->user(),
            'today'     => $today,
            'week'      => $week,
            'total'     => FieldVisit::forOwner($ownerId)->count(),
            'followUps' => $followUps,
            'recent'    => $recent,
        ]);
    }

    public function index(Request $request): View
    {
        $ownerId = $this->ownerId();
        $q = trim((string) $request->query('q', ''));

        $visits = FieldVisit::forOwner($ownerId)
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('place_name', 'like', "%{$q}%")
                  ->orWhere('area', 'like', "%{$q}%")
                  ->orWhere('contact_name', 'like', "%{$q}%")))
            ->orderByDesc('visited_at')
            ->paginate(15)
            ->withQueryString();

        return view('employee.field-visits.index', ['visits' => $visits, 'q' => $q]);
    }

    public function create(): View
    {
        return view('employee.field-visits.create', [
            'placeTypes' => FieldVisit::PLACE_TYPES,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'place_name'        => ['required', 'string', 'max:190'],
            'area'              => ['nullable', 'string', 'max:190'],
            'place_type'        => ['nullable', 'string', 'in:' . implode(',', array_keys(FieldVisit::PLACE_TYPES))],
            'contact_name'      => ['nullable', 'string', 'max:190'],
            'contact_phone'     => ['nullable', 'string', 'max:40'],
            'explained_glowrez' => ['nullable', 'boolean'],
            'current_system'    => ['nullable', 'string', 'max:190'],
            'problems'          => ['nullable', 'string', 'max:2000'],
            'opinion'           => ['nullable', 'string', 'max:2000'],
            'interest_level'    => ['nullable', 'integer', 'between:1,5'],
            'follow_up_date'    => ['nullable', 'date', 'after_or_equal:today'],
            'notes'             => ['nullable', 'string', 'max:2000'],
            // Silent presence signals from the browser.
            'lat'               => ['nullable', 'numeric', 'between:-90,90'],
            'lng'               => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy'      => ['nullable', 'numeric', 'min:0'],
            'gps_denied'        => ['nullable', 'boolean'],
            'source'            => ['nullable', 'string', 'in:mobile,desktop,web'],
            'photo'             => ['nullable', 'image', 'max:8192'],
        ]);

        $ownerId = $this->ownerId();

        // The immediately-preceding visit powers the travel-plausibility check.
        $previous = FieldVisit::forOwner($ownerId)->orderByDesc('visited_at')->first();

        $visit = new FieldVisit($data);
        $visit->owner_id   = $ownerId;
        $visit->visited_at = now();
        $visit->gps_denied = $request->boolean('gps_denied');
        $visit->source     = $data['source'] ?? 'web';
        $visit->user_agent = substr((string) $request->userAgent(), 0, 500);
        $visit->ip         = $request->ip();

        if ($request->hasFile('photo')) {
            $visit->photo_path = $request->file('photo')->store('field-visits', 'public');
        }

        $visit->save();

        // Manager-only verification (rep never sees the result).
        $this->runVerification($visit, $previous);

        return redirect()
            ->route('employee.field-visits.show', $visit)
            ->with('success', __('Visit saved. Good work!'));
    }

    public function show(FieldVisit $visit): View
    {
        $this->ownVisit($visit);

        return view('employee.field-visits.show', ['visit' => $visit]);
    }

    public function checkout(FieldVisit $visit, Request $request): RedirectResponse
    {
        $this->ownVisit($visit);

        if ($visit->checked_out_at) {
            return back()->with('info', __('This visit is already checked out.'));
        }

        $data = $request->validate([
            'lat'          => ['nullable', 'numeric', 'between:-90,90'],
            'lng'          => ['nullable', 'numeric', 'between:-180,180'],
            'gps_accuracy' => ['nullable', 'numeric', 'min:0'],
        ]);

        $visit->checked_out_at    = now();
        // Epoch difference: timezone-agnostic and always a non-negative integer
        // (the column is unsigned, and Carbon's diffInSeconds is signed/float).
        $visit->dwell_seconds     = $visit->visited_at
            ? max(0, now()->getTimestamp() - $visit->visited_at->getTimestamp())
            : null;
        $visit->checkout_lat      = $data['lat'] ?? null;
        $visit->checkout_lng      = $data['lng'] ?? null;
        $visit->checkout_accuracy = $data['gps_accuracy'] ?? null;
        $visit->save();

        return back()->with('success', __('Checked out.'));
    }

    /** Score the visit's presence signals and persist the manager-facing fields. */
    private function runVerification(FieldVisit $visit, ?FieldVisit $previous): void
    {
        $sameSpot = 0;
        if ($visit->hasLocation()) {
            $prior = FieldVisit::forOwner($visit->owner_id)
                ->where('id', '!=', $visit->id)
                ->whereNotNull('lat')->whereNotNull('lng')
                ->latest('visited_at')->limit(300)->get(['lat', 'lng']);
            foreach ($prior as $p) {
                if ($this->verifier->haversine((float) $visit->lat, (float) $visit->lng, (float) $p->lat, (float) $p->lng) <= 60) {
                    $sameSpot++;
                }
            }
        }

        $result = $this->verifier->evaluate($visit, $previous, $sameSpot);

        $visit->forceFill([
            'verification'     => $result['signals'] ?? [],
            'confidence_score' => $result['score'] ?? null,
            'flagged'          => (bool) ($result['flagged'] ?? false),
            'flag_reasons'     => $result['reasons'] ?? [],
        ])->save();
    }
}
