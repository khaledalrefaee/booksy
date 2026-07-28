<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\TransitionActor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One hop in an appointment's life cycle. Append-only — never updated.
 */
class AppointmentTransition extends Model
{
    /** The log is immutable, so there is nothing to update a timestamp for. */
    public const UPDATED_AT = null;

    protected $fillable = [
        'appointment_id',
        'company_id',
        'from_status',
        'to_status',
        'actor_type',
        'actor_id',
        'actor_name',
        'reason',
        'automatic',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => AppointmentStatus::class,
            'to_status'   => AppointmentStatus::class,
            'actor_type'  => TransitionActor::class,
            'automatic'   => 'boolean',
            'meta'        => 'array',
            'created_at'  => 'datetime',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
