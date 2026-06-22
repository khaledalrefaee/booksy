<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AppointmentConfirmation extends Model
{
    protected $fillable = [
        'appointment_id', 'token', 'action', 'acted_at', 'expires_at',
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
}
