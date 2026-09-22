<?php

namespace App\Http\Controllers\Company;

use App\Enums\BookingSource;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Branch;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class MarketingController extends Controller
{
    private function company(): \App\Models\Company
    {
        return Auth::guard('company')->user();
    }

    /**
     * Marketing → Offers.
     *
     * "Offers" are not a separate system: they are services that carry a
     * discount (discount_type + discount_value). This page simply gathers every
     * such service across the company's branches into one place and links back
     * to the existing service editor — no new tables, no new business logic.
     */
    public function offers(Request $request)
    {
        $company   = $this->company();
        $branches  = $company->branches()->orderBy('sort_order')->get();
        $branchIds = $branches->pluck('id');

        $services = Service::whereIn('branch_id', $branchIds)
            ->whereNotNull('discount_type')
            ->whereNotNull('discount_value')
            ->with(['branch', 'serviceCategory'])
            ->orderByDesc('updated_at')
            ->get();

        $now = now();
        // Classify each offer by lifecycle so the UI can group them.
        $services->each(function (Service $s) use ($now) {
            if ($s->discount_starts_at && $now->lt($s->discount_starts_at)) {
                $s->offer_state = 'scheduled';
            } elseif ($s->discount_ends_at && $now->gt($s->discount_ends_at)) {
                $s->offer_state = 'expired';
            } else {
                $s->offer_state = 'active';
            }
        });

        $active    = $services->where('offer_state', 'active')->values();
        $scheduled = $services->where('offer_state', 'scheduled')->values();
        $expired   = $services->where('offer_state', 'expired')->values();

        $stats = [
            'active'    => $active->count(),
            'scheduled' => $scheduled->count(),
            'expired'   => $expired->count(),
            'total'     => $services->count(),
        ];

        return view('company.marketing.offers', compact(
            'active', 'scheduled', 'expired', 'stats', 'branches'
        ));
    }

    /**
     * Marketing → Social Media & Booking Sources.
     *
     * Shows each branch's shareable tracking links and real booking counts per
     * source, computed straight from the appointments table.
     */
    public function bookingSources(Request $request)
    {
        $company  = $this->company();
        $branches = $company->branches()->orderBy('sort_order')->get();

        if ($branches->isEmpty()) {
            return view('company.marketing.booking-sources', [
                'branches'   => $branches,
                'selBranch'  => null,
                'range'      => 'all',
                'rows'       => collect(),
                'total'      => 0,
            ]);
        }

        // Selected branch (falls back to the first branch).
        $selBranch = $branches->firstWhere('id', (int) $request->query('branch'))
            ?? $branches->first();

        $range = $request->query('range', 'all');

        $query = Appointment::where('branch_id', $selBranch->id);
        $this->applyRange($query, $range);

        // Real counts grouped by stored source.
        $counts = $query->selectRaw('booking_source, COUNT(*) as c')
            ->groupBy('booking_source')
            ->pluck('c', 'booking_source');

        $total = (int) $counts->sum();

        // "Other" = everything that is not one of the five explicit sources:
        // untracked / direct / legacy (null) bookings, and any stray value.
        // Derived by subtraction so a null key is never double-counted.
        $knownTotal = 0;
        foreach ([BookingSource::Instagram, BookingSource::Facebook, BookingSource::Whatsapp, BookingSource::Website, BookingSource::Reception] as $known) {
            $knownTotal += (int) ($counts[$known->value] ?? 0);
        }
        $otherCount = max(0, $total - $knownTotal);

        // Display order the merchant expects.
        $order = [
            BookingSource::Instagram,
            BookingSource::Facebook,
            BookingSource::Whatsapp,
            BookingSource::Website,
            BookingSource::Reception,
            BookingSource::Other,
        ];

        $rows = collect($order)->map(function (BookingSource $src) use ($counts, $otherCount, $total, $selBranch) {
            $count = $src === BookingSource::Other
                ? $otherCount
                : (int) ($counts[$src->value] ?? 0);

            return [
                'source'  => $src,
                'label'   => $src->label(),
                'iconKey' => $src->iconKey(),
                'color'   => $src->color(),
                'code'    => $src->code(),
                'url'     => $src->code() ? $selBranch->trackingUrl($src->code()) : null,
                'count'   => $count,
                'pct'     => $total > 0 ? round($count / $total * 100) : 0,
            ];
        });

        return view('company.marketing.booking-sources', compact(
            'branches', 'selBranch', 'range', 'rows', 'total'
        ));
    }

    private function applyRange($query, string $range): void
    {
        match ($range) {
            'today' => $query->whereDate('created_at', today()),
            '7d'    => $query->where('created_at', '>=', Carbon::now()->subDays(7)),
            '30d'   => $query->where('created_at', '>=', Carbon::now()->subDays(30)),
            'month' => $query->where('created_at', '>=', Carbon::now()->startOfMonth()),
            default => null, // all time
        };
    }
}
