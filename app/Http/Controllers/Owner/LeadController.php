<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Services\Leads\LeadReport;
use App\Services\Owner\OwnerAudit;
use App\Support\LeadCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * The CRM side of the pre-launch funnel: every business that submitted /join,
 * filterable, sortable, with a status pipeline, notes and a timeline.
 * Reading needs leads.view; changing status / adding notes needs leads.manage.
 */
class LeadController extends Controller
{
    private const PER_PAGE = 20;

    private const SORTS = ['newest', 'oldest', 'branches', 'name', 'status'];

    public function index(Request $request): View
    {
        $f = $this->filters($request);

        $leads = $this->filtered($f)
            ->tap(fn (Builder $q) => $this->sorted($q, $f['sort']))
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $report = app(LeadReport::class)->build(['date_from' => $f['date_from'], 'date_to' => $f['date_to']]);

        return view('owner.leads.index', [
            'leads'       => $leads,
            'filters'     => $f,
            'report'      => $report,
            'activeCount' => $this->activeFilterCount($f),
            'options'     => $this->filterOptions(),
            'canManage'   => $request->user('owner')?->hasPermission('leads.manage') ?? false,
            'mailReady'   => (bool) config('leads.notification_email'),
        ]);
    }

    public function show(Lead $lead, Request $request): View
    {
        $lead->load(['activities.owner:id,name', 'contactedBy:id,name']);

        return view('owner.leads.show', [
            'lead'      => $lead,
            'canManage' => $request->user('owner')?->hasPermission('leads.manage') ?? false,
        ]);
    }

    public function updateStatus(Request $request, Lead $lead): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(LeadCatalog::keys('statuses'))],
        ]);

        $from = $lead->status;
        $to   = $data['status'];

        if ($from !== $to) {
            $owner = Auth::guard('owner')->user();

            DB::transaction(function () use ($lead, $from, $to, $owner) {
                $lead->status = $to;

                // First time anyone moves it past "new" = first contact.
                if ($to !== 'new' && $lead->contacted_at === null) {
                    $lead->contacted_at = now();
                    $lead->contacted_by = $owner?->id;
                }

                $lead->save();

                $lead->activities()->create([
                    'owner_id'   => $owner?->id,
                    'type'       => LeadActivity::STATUS_CHANGED,
                    'meta'       => ['from' => $from, 'to' => $to],
                    'created_at' => now(),
                ]);
            });

            OwnerAudit::record('lead.status', $lead, ['status' => $from], ['status' => $to], label: $lead->business_name);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => true,
                'message' => __('Status updated.'),
                'data'    => ['status' => $lead->status, 'label' => $lead->statusLabel(), 'tone' => $lead->statusTone()],
            ]);
        }

        return back()->with('success', __('Status updated.'));
    }

    public function storeNote(Request $request, Lead $lead): JsonResponse|RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $body = trim(strip_tags($data['body']));
        if ($body === '') {
            return $this->noteError($request);
        }

        $activity = $lead->activities()->create([
            'owner_id'   => Auth::guard('owner')->id(),
            'type'       => LeadActivity::NOTE,
            'body'       => $body,
            'created_at' => now(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'status'  => true,
                'message' => __('Note added.'),
                'data'    => ['id' => $activity->id],
            ]);
        }

        return back()->with('success', __('Note added.'));
    }

    public function export(Request $request): StreamedResponse
    {
        $f = $this->filters($request);
        $query = $this->filtered($f);
        $this->sorted($query, $f['sort']);

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");   // Excel-friendly UTF-8
            fputcsv($out, [
                __('Name'), __('Phone'), 'WhatsApp', __('Email'), __('Business'), __('Business type'),
                __('City'), __('Area'), __('Branches'), __('Interests'), __('Status'),
                __('Source'), __('Campaign'), 'UTM source', 'UTM medium', 'UTM campaign', 'UTM content',
                __('Interactions'), __('Registered'), __('Contacted at'),
            ]);
            $query->chunkById(500, function ($rows) use ($out) {
                foreach ($rows as $l) {
                    fputcsv($out, array_map([self::class, 'csvSafe'], [
                        $l->full_name, $l->phone, $l->whatsapp, $l->email, $l->business_name, $l->businessTypeLabel(),
                        $l->cityLabel(), $l->area, $l->number_of_branches, implode(' | ', $l->interestLabels()), $l->statusLabel(),
                        $l->sourceLabel(), $l->campaign, $l->utm_source, $l->utm_medium, $l->utm_campaign, $l->utm_content,
                        $l->interaction_count, $l->created_at?->format('Y-m-d H:i'), $l->contacted_at?->format('Y-m-d H:i'),
                    ]));
                }
            });
            fclose($out);
        }, 'glowrez-leads-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Visitors type these values, admins open the file in Excel: a cell starting with = + - @ would
     * run as a formula. Prefix those with an apostrophe (phone-looking values stay untouched).
     */
    public static function csvSafe(mixed $v): mixed
    {
        if (! is_string($v) || $v === '' || ! preg_match('/^[=+\-@\t\r]/', $v)) {
            return $v;
        }

        return preg_match('/^\+?[\d\s\-()]+$/', $v) ? $v : "'".$v;
    }

    /* ── filtering / sorting ────────────────────────────────────────────── */

    private function filters(Request $request): array
    {
        $s = fn (string $k) => is_string($request->query($k)) ? trim($request->query($k)) : '';

        $date = fn (string $k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', $s($k)) ? $s($k) : null;

        return [
            'q'             => $s('q'),
            'business_type' => $s('business_type'),
            'city'          => $s('city'),
            'area'          => $s('area'),
            'branches'      => in_array($s('branches'), ['1', '2', '3', '4', '5+'], true) ? $s('branches') : '',
            'source'        => $s('source'),
            'campaign'      => $s('campaign'),
            'status'        => in_array($s('status'), LeadCatalog::keys('statuses'), true) ? $s('status') : '',
            'date_from'     => $date('date_from'),
            'date_to'       => $date('date_to'),
            'sort'          => in_array($s('sort'), self::SORTS, true) ? $s('sort') : 'newest',
        ];
    }

    private function filtered(array $f): Builder
    {
        $like = fn (string $v) => '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $v).'%';

        return Lead::query()
            ->search($f['q'])
            ->when($f['business_type'] !== '', fn ($q) => $q->where('business_type', $f['business_type']))
            ->when($f['city'] !== '', fn ($q) => $q->where('city', $f['city']))
            ->when($f['area'] !== '', fn ($q) => $q->where('area', 'like', $like($f['area'])))
            ->when($f['branches'] !== '', fn ($q) => $f['branches'] === '5+'
                ? $q->where('number_of_branches', '>=', 5)
                : $q->where('number_of_branches', (int) $f['branches']))
            ->when($f['source'] !== '', fn ($q) => $q->where('source', $f['source']))
            ->when($f['campaign'] !== '', fn ($q) => $q->where('campaign', $f['campaign']))
            ->when($f['status'] !== '', fn ($q) => $q->where('status', $f['status']))
            ->when($f['date_from'], fn ($q, $d) => $q->whereDate('created_at', '>=', $d))
            ->when($f['date_to'], fn ($q, $d) => $q->whereDate('created_at', '<=', $d));
    }

    private function sorted(Builder $q, string $sort): Builder
    {
        return match ($sort) {
            'oldest'   => $q->orderBy('created_at')->orderBy('id'),
            'branches' => $q->orderByDesc('number_of_branches')->orderByDesc('created_at'),
            'name'     => $q->orderBy('full_name')->orderBy('id'),
            'status'   => $q->orderByRaw($this->statusOrderSql())->orderByDesc('created_at'),
            default    => $q->orderByDesc('created_at')->orderByDesc('id'),
        };
    }

    /** Pipeline order (new first … lost last) rather than alphabetical. */
    private function statusOrderSql(): string
    {
        $cases = collect(LeadCatalog::keys('statuses'))
            ->map(fn ($k, $i) => "WHEN '".$k."' THEN ".$i)
            ->implode(' ');

        return "CASE status {$cases} ELSE 99 END";
    }

    private function activeFilterCount(array $f): int
    {
        return collect($f)->except(['sort'])->filter(fn ($v) => $v !== '' && $v !== null)->count();
    }

    /** Values for the filter dropdowns: the catalogue, plus whatever free text visitors actually typed. */
    private function filterOptions(): array
    {
        $cities = collect(LeadCatalog::options('cities'));
        Lead::query()->whereNotNull('city')->distinct()->pluck('city')
            ->each(function ($c) use (&$cities) {
                if (! $cities->has($c) && $c !== 'other') {
                    $cities->put($c, $c);
                }
            });

        return [
            'cities'    => $cities->all(),
            'campaigns' => Lead::query()->whereNotNull('campaign')->distinct()->orderBy('campaign')->pluck('campaign')->all(),
            'areas'     => Lead::query()->whereNotNull('area')->distinct()->orderBy('area')->limit(200)->pluck('area')->all(),
        ];
    }

    private function noteError(Request $request): JsonResponse|RedirectResponse
    {
        $msg = __('Write a note first.');

        return $request->expectsJson()
            ? response()->json(['status' => false, 'message' => $msg, 'data' => null], 422)
            : back()->withErrors(['body' => $msg]);
    }
}
