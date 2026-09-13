<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Owner\Concerns\ResolvesOwnerCompany;
use App\Models\FieldVisit;
use App\Models\Owner;
use App\Services\FieldSales\VisitVerification;
use App\Services\Owner\OwnerAudit;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Owner-side field-sales operations: the manager view over every rep's visits,
 * the verification/review queue, and a map of where visits were filed. Reps log
 * visits in the /employee area; managers monitor and approve here.
 */
class FieldSalesController extends Controller
{
    use ResolvesOwnerCompany;

    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $f = $this->parseFilters($request);

        $visits = $this->filtered($f)
            ->with('owner')
            ->orderByDesc('visited_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('owner.field-sales.index', array_merge($this->shared($f), [
            'visits' => $visits,
            'mode'   => 'all',
            'stats'  => $this->stats(),
        ]));
    }

    public function review(Request $request): View
    {
        $f = $this->parseFilters($request);

        // The queue: anything flagged or still pending, worst confidence first.
        $visits = $this->filtered($f)
            ->where(fn ($q) => $q->where('flagged', true)->orWhere('review_status', 'pending'))
            ->with('owner')
            ->orderByRaw('flagged DESC')
            ->orderByRaw('confidence_score IS NULL, confidence_score ASC')
            ->orderByDesc('visited_at')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return view('owner.field-sales.index', array_merge($this->shared($f), [
            'visits' => $visits,
            'mode'   => 'review',
            'stats'  => $this->stats(),
        ]));
    }

    public function map(Request $request): View
    {
        $f = $this->parseFilters($request);

        $points = $this->filtered($f)
            ->whereNotNull('lat')->whereNotNull('lng')
            ->with('owner:id,name')
            ->latest('visited_at')
            ->limit(1000)
            ->get(['id', 'place_name', 'area', 'lat', 'lng', 'owner_id', 'visited_at', 'confidence_score', 'flagged']);

        return view('owner.field-sales.map', array_merge($this->shared($f), [
            'points' => $points->map(fn ($v) => [
                'id'    => $v->id,
                'name'  => $v->place_name,
                'area'  => $v->area,
                'lat'   => (float) $v->lat,
                'lng'   => (float) $v->lng,
                'rep'   => $v->owner?->name,
                'when'  => $v->visited_at?->translatedFormat('M j, H:i'),
                'flagged' => (bool) $v->flagged,
                'url'   => route('owner.field-sales.show', $v),
            ])->values(),
        ]));
    }

    public function show(FieldVisit $visit): View
    {
        $visit->load('owner', 'reviewer');

        return view('owner.field-sales.show', [
            'visit'        => $visit,
            'band'         => $visit->confidenceBand(),
            'reasonLabels' => VisitVerification::reasonLabels(),
            'canReview'    => \Illuminate\Support\Facades\Gate::allows('owner-can', 'field-visits.review'),
        ]);
    }

    public function storeReview(FieldVisit $visit, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'review_status' => ['required', 'in:approved,rejected'],
            'review_note'   => ['nullable', 'string', 'max:1000'],
        ]);

        $visit->forceFill([
            'review_status' => $data['review_status'],
            'review_note'   => $data['review_note'] ?? null,
            'reviewed_by'   => auth('owner')->id(),
            'reviewed_at'   => now(),
        ])->save();

        OwnerAudit::record('field-visit.reviewed', $visit,
            new: ['status' => $data['review_status']], label: $visit->place_name);

        return back()->with('success', __('Visit review saved.'));
    }

    public function export(Request $request): StreamedResponse
    {
        $f = $this->parseFilters($request);
        $query = $this->filtered($f)->with('owner')->orderByDesc('visited_at');
        $tz = config('app.timezone');

        return response()->streamDownload(function () use ($query, $tz) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [
                __('Rep'), __('Place name'), __('Area'), __('Contact person'), __('Contact phone'),
                __('Interest level'), __('Date & time'), __('Checked out'), __('Dwell (min)'),
                __('Confidence'), __('Flagged'), __('Review status'),
            ]);
            $query->chunk(500, function ($rows) use ($out, $tz) {
                foreach ($rows as $v) {
                    fputcsv($out, [
                        $v->owner?->name ?? '',
                        $v->place_name, $v->area, $v->contact_name, $v->contact_phone,
                        $v->interest_level, $v->visited_at?->timezone($tz)->format('Y-m-d H:i'),
                        $v->checked_out_at?->timezone($tz)->format('Y-m-d H:i'),
                        $v->dwell_seconds ? round($v->dwell_seconds / 60, 1) : '',
                        $v->confidence_score, $v->flagged ? __('Yes') : __('No'), $v->review_status,
                    ]);
                }
            });
            fclose($out);
        }, 'field-visits-' . now()->format('Y-m-d-His') . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ─────────────── internals ───────────────

    /** @return array<string, mixed> */
    private function parseFilters(Request $request): array
    {
        $validated = $request->validate([
            'rep'       => ['nullable', 'integer', 'exists:owners,id'],
            'flagged'   => ['nullable', 'boolean'],
            'review'    => ['nullable', 'in:pending,approved,rejected'],
            'date_from' => ['nullable', 'date'],
            'date_to'   => ['nullable', 'date'],
        ]);

        return [
            'q'         => trim((string) $request->input('q', '')),
            'rep'       => ! empty($validated['rep']) ? (int) $validated['rep'] : null,
            'flagged'   => $request->boolean('flagged') ? true : null,
            'review'    => $validated['review'] ?? null,
            'date_from' => $validated['date_from'] ?? null,
            'date_to'   => $validated['date_to'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $f
     * @return Builder<FieldVisit>
     */
    private function filtered(array $f): Builder
    {
        return FieldVisit::query()
            ->when($f['rep'], fn ($q) => $q->where('owner_id', $f['rep']))
            ->when($f['flagged'], fn ($q) => $q->where('flagged', true))
            ->when($f['review'], fn ($q) => $q->where('review_status', $f['review']))
            ->when($f['date_from'], fn ($q) => $q->whereDate('visited_at', '>=', $f['date_from']))
            ->when($f['date_to'], fn ($q) => $q->whereDate('visited_at', '<=', $f['date_to']))
            ->when($f['q'] !== '', fn ($q) => $q->where(fn ($w) =>
                $w->where('place_name', 'like', "%{$f['q']}%")
                  ->orWhere('area', 'like', "%{$f['q']}%")
                  ->orWhere('contact_name', 'like', "%{$f['q']}%")));
    }

    /** @param array<string,mixed> $f @return array<string,mixed> */
    private function shared(array $f): array
    {
        return [
            'filters' => $f,
            'reps'    => Owner::query()
                ->whereIn('role', ['field_sales', 'sales_manager'])
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        return [
            'total'   => FieldVisit::count(),
            'today'   => FieldVisit::whereDate('visited_at', today())->count(),
            'flagged' => FieldVisit::where('flagged', true)->count(),
            'pending' => FieldVisit::where('review_status', 'pending')->count(),
        ];
    }
}
