<?php

namespace App\Services\Leads;

use App\Models\Lead;
use App\Models\LeadVisit;
use App\Support\LeadCatalog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Numbers for the top of /owner/leads: pipeline counts, and "who are the
 * businesses that want GlowRez" broken down by source / type / city / campaign /
 * branches, with unique visitors + conversion rate where we have visits.
 * Everything is a SQL GROUP BY, so it stays cheap as the lead list grows.
 */
class LeadReport
{
    /** @param array{date_from?:?string,date_to?:?string} $range */
    public function build(array $range): array
    {
        $leads  = fn (): Builder => $this->ranged(Lead::query(), 'leads.created_at', $range);
        $visits = fn (): Builder => $this->ranged(LeadVisit::query(), 'lead_visits.created_at', $range);

        $byStatus = $leads()->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status')->all();
        $total    = array_sum($byStatus);
        $visitors = (int) $visits()->distinct()->count('visitor_id');

        // Unique visitors per source/campaign, to turn the lead counts into conversion rates.
        $visitorsBySource   = $visits()->selectRaw('source, COUNT(DISTINCT visitor_id) c')->groupBy('source')->pluck('c', 'source')->all();
        $visitorsByCampaign = $visits()->whereNotNull('campaign')->selectRaw('campaign, COUNT(DISTINCT visitor_id) c')->groupBy('campaign')->pluck('c', 'campaign')->all();

        $group = fn (string $col, int $limit = 12) => $leads()
            ->whereNotNull($col)->where($col, '!=', '')
            ->selectRaw("$col k, COUNT(*) c")->groupBy($col)->orderByDesc('c')->limit($limit)->pluck('c', 'k')->all();

        $sources = $this->withRate($group('source'), $visitorsBySource, fn ($k) => LeadCatalog::label('sources', $k));
        // Sources with visitors but no leads yet are still worth seeing (that is the leak).
        foreach ($visitorsBySource as $k => $v) {
            if (! isset($sources[$k])) {
                $sources[$k] = ['key' => $k, 'label' => LeadCatalog::label('sources', $k), 'count' => 0, 'visitors' => (int) $v, 'rate' => 0.0];
            }
        }

        return [
            'total'     => $total,
            'today'     => (int) Lead::query()->whereDate('created_at', now()->toDateString())->count(),
            'by_status' => $byStatus,
            'visitors'  => $visitors,
            'rate'      => $visitors > 0 ? round($total / $visitors * 100, 1) : null,
            'sources'   => collect($sources)->sortByDesc('count')->values()->all(),
            'types'     => $this->labelled($group('business_type'), 'business_types'),
            'cities'    => $this->labelled($group('city'), 'cities'),
            'campaigns' => $this->withRate($group('campaign', 8), $visitorsByCampaign, fn ($k) => $k),
            'branches'  => $this->branchBuckets($leads()),
        ];
    }

    private function ranged(Builder $q, string $col, array $range): Builder
    {
        return $q
            ->when(! empty($range['date_from']), fn ($w) => $w->whereDate($col, '>=', $range['date_from']))
            ->when(! empty($range['date_to']), fn ($w) => $w->whereDate($col, '<=', $range['date_to']));
    }

    /** [key ⇒ count] → [['key','label','count','pct']…] sorted desc. */
    private function labelled(array $counts, string $group): array
    {
        $sum = max(1, array_sum($counts));

        return collect($counts)->map(fn ($c, $k) => [
            'key'   => (string) $k,
            'label' => LeadCatalog::label($group, (string) $k),
            'count' => (int) $c,
            'pct'   => round($c / $sum * 100),
        ])->values()->all();
    }

    private function withRate(array $counts, array $visitors, \Closure $label): array
    {
        $out = [];
        foreach ($counts as $k => $c) {
            $v = (int) ($visitors[$k] ?? 0);
            $out[$k] = [
                'key'      => (string) $k,
                'label'    => $label($k),
                'count'    => (int) $c,
                'visitors' => $v,
                'rate'     => $v > 0 ? round($c / $v * 100, 1) : null,
            ];
        }

        return $out;
    }

    /** Single-site vs small chain vs large chain — what size of customer is waiting. */
    private function branchBuckets(Builder $q): array
    {
        $row = $q->selectRaw(
            'SUM(number_of_branches = 1) b1, SUM(number_of_branches = 2) b2, '.
            'SUM(number_of_branches BETWEEN 3 AND 5) b3, SUM(number_of_branches > 5) b4'
        )->first();

        $counts = [(int) ($row->b1 ?? 0), (int) ($row->b2 ?? 0), (int) ($row->b3 ?? 0), (int) ($row->b4 ?? 0)];
        $labels = [__('1 branch'), __('2 branches'), __('3–5 branches'), __('6+ branches')];
        $sum    = max(1, array_sum($counts));

        return collect($counts)->map(fn ($c, $i) => ['key' => (string) $i, 'label' => $labels[$i], 'count' => $c, 'pct' => round($c / $sum * 100)])->all();
    }
}
