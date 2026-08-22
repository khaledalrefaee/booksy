<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AppointmentConfirmation extends Model
{
    protected $fillable = [
        'appointment_id', 'token', 'action', 'reason', 'acted_at', 'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'acted_at'   => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function isExpired(): bool
    {
        return now()->gt($this->expires_at);
    }

    public function isUsed(): bool
    {
        return $this->action !== null;
    }

    public static function generateFor(Appointment $appointment): self
    {
        return self::create([
            'appointment_id' => $appointment->id,
            'token'          => Str::random(48),
            'expires_at'     => $appointment->start_time,
        ]);
    }

    /**
     * A still-usable confirmation for this appointment, reused across the booking
     * message and the 1h reminder so both links act on the same token. Creates a
     * fresh one only when none is pending, keeping the confirm/cancel flow single.
     */
    public static function activeFor(Appointment $appointment): self
    {
        $existing = self::where('appointment_id', $appointment->id)
            ->whereNull('action')
            ->where('expires_at', '>', now())
            ->latest('id')
            ->first();

        return $existing ?? self::generateFor($appointment);
    }
}
