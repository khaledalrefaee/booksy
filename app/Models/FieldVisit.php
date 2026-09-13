<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One field-sales visit. Belongs to the staff member who filed it (owner_id).
 * Presence signals are captured at submit; verification fields are computed by
 * App\Services\FieldSales\VisitVerification and shown to managers only.
 */
class FieldVisit extends Model
{
    protected $fillable = [
        'owner_id',
        'place_name', 'area', 'place_type', 'contact_name', 'contact_phone',
        'explained_glowrez', 'current_system', 'problems', 'opinion',
        'interest_level', 'follow_up_date', 'notes',
        'visited_at', 'checked_out_at', 'dwell_seconds',
        'lat', 'lng', 'gps_accuracy',
        'checkout_lat', 'checkout_lng', 'checkout_accuracy',
        'gps_denied', 'resolved_address', 'photo_path', 'photo_taken_at',
        'source', 'user_agent', 'ip',
        'verification', 'confidence_score', 'flagged', 'flag_reasons',
        'review_status', 'reviewed_by', 'reviewed_at', 'review_note',
    ];

    protected function casts(): array
    {
        return [
            'explained_glowrez' => 'boolean',
            'gps_denied'        => 'boolean',
            'flagged'           => 'boolean',
            'interest_level'    => 'integer',
            'confidence_score'  => 'integer',
            'dwell_seconds'     => 'integer',
            'follow_up_date'    => 'date',
            'visited_at'        => 'datetime',
            'checked_out_at'    => 'datetime',
            'photo_taken_at'    => 'datetime',
            'reviewed_at'       => 'datetime',
            'verification'      => 'array',
            'flag_reasons'      => 'array',
        ];
    }

    /** Place-type option slugs → translation keys (labels resolved in views). */
    public const PLACE_TYPES = [
        'salon'   => 'Beauty salon',
        'spa'     => 'Spa / wellness',
        'clinic'  => 'Clinic',
        'barber'  => 'Barbershop',
        'gym'     => 'Gym / fitness',
        'nails'   => 'Nails / lashes',
        'other'   => 'Other',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'owner_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'reviewed_by');
    }

    public function hasLocation(): bool
    {
        return $this->lat !== null && $this->lng !== null;
    }

    /** Manager-facing confidence band from the score. */
    public function confidenceBand(): string
    {
        return match (true) {
            $this->confidence_score === null => 'unknown',
            $this->confidence_score >= 75    => 'high',
            $this->confidence_score >= 45    => 'medium',
            default                          => 'low',
        };
    }

    public function scopeForOwner($query, int $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    public function scopeFlagged($query)
    {
        return $query->where('flagged', true);
    }

    public function scopePending($query)
    {
        return $query->where('review_status', 'pending');
    }
}
