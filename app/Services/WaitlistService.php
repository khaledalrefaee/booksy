<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\BookingWaitlistEntry;

/**
 * Matches a freed appointment slot to waiting customers and notifies them. Kept
 * out of controllers/observers so the rule lives in one place.
 */
class WaitlistService
{
    public function __construct(private readonly WhatsappService $whatsapp)
    {
    }

    /**
     * A slot just opened (an appointment was cancelled). Notify the oldest waiting
     * customer whose branch + service + day + time window match. Only one is
     * notified — the booking itself is race-safe (DB lock), so this is fairness,
     * not correctness. Returns the notified entry, or null.
     */
    public function notifyForFreedSlot(Appointment $freed): ?BookingWaitlistEntry
    {
        if (! $freed->branch_id || ! $freed->service_id) {
            return null;
        }

        $date = $freed->start_time->toDateString();
        $time = $freed->start_time->format('H:i:s');

        $candidates = BookingWaitlistEntry::query()
            ->waiting()
            ->where('branch_id', $freed->branch_id)
            ->where('preferred_date', $date)
            ->where(fn ($q) => $q->whereNull('service_id')->orWhere('service_id', $freed->service_id))
            ->with(['branch', 'service', 'customer'])
            ->orderBy('created_at')
            ->get();

        foreach ($candidates as $entry) {
            if (! $entry->matchesTime($time)) {
                continue;
            }

            $sent = $this->whatsapp->sendWaitlistOpening($entry, $freed);

            $entry->update([
                'status'      => 'notified',
                'notified_at' => now(),
                'hold_until'  => now()->addMinutes(30), // soft priority window
            ]);

            return $sent ? $entry : $entry; // notified regardless of gateway result
        }

        return null;
    }
}
