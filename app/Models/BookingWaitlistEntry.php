<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Online booking waitlist entry — a customer waiting to be told when a slot at a
 * branch (for a service, on a day, optionally within a time window) opens up.
 * Distinct from {@see WaitlistEntry}, the in-salon reception queue.
 */
class BookingWaitlistEntry extends Model
{
    protected $table = 'booking_waitlist';

    protected $fillable = [
        'customer_id', 'company_id', 'branch_id', 'service_id',
        'preferred_date', 'pref_from', 'pref_to',
        'status', 'notified_at', 'hold_until',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'notified_at'    => 'datetime',
            'hold_until'     => 'datetime',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function service(): BelongsTo  { return $this->belongsTo(Service::class); }

    public function scopeWaiting($q) { return $q->where('status', 'waiting'); }

    /** Does a freed slot at $time (H:i:s) fall within this entry's window? */
    public function matchesTime(string $time): bool
    {
        if (! $this->pref_from || ! $this->pref_to) {
            return true; // any time that day
        }
        return $time >= $this->pref_from && $time < $this->pref_to;
    }
}
