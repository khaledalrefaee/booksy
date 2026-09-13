<?php

namespace App\Services\FieldSales;

use App\Models\FieldVisit;

/**
 * Turns the raw presence signals on a field visit into a manager-facing
 * confidence score (0..100) plus human-readable flags.
 *
 * Philosophy: no single signal is proof — a rooted phone can spoof GPS and
 * EXIF can be edited. Instead we layer cheap signals so that fabricating a
 * convincing day of visits becomes expensive and statistically obvious. The
 * rep never sees any of this; it surfaces only in the owner's review queue.
 */
class VisitVerification
{
    /** km/h above which travel between two consecutive visits is "impossible". */
    private const MAX_PLAUSIBLE_KMH = 130;

    /** metres: prior visits closer than this count as the "same spot". */
    private const SAME_SPOT_RADIUS_M = 60;

    /** how many prior same-spot visits before we flag "always the same place". */
    private const SAME_SPOT_LIMIT = 3;

    /**
     * @param  FieldVisit       $visit        the visit being evaluated (unsaved or saved)
     * @param  FieldVisit|null  $previous     the rep's immediately preceding visit
     * @param  int              $sameSpotPriors  count of prior visits within SAME_SPOT_RADIUS_M
     * @return array{score:int, flagged:bool, reasons:array<int,string>, signals:array<string,mixed>}
     */
    public function evaluate(FieldVisit $visit, ?FieldVisit $previous = null, int $sameSpotPriors = 0): array
    {
        $score   = 100;
        $reasons = [];
        $signals = [];

        // ── Location presence & quality ──────────────────────────────────
        if ($visit->gps_denied) {
            $score -= 60;
            $reasons[] = 'no_gps';
            $signals['location'] = 'denied';
        } elseif (! $visit->hasLocation()) {
            $score -= 40;
            $reasons[] = 'no_location';
            $signals['location'] = 'missing';
        } else {
            $acc = $visit->gps_accuracy;
            $signals['accuracy_m'] = $acc;
            if ($acc !== null && $acc > 200) {
                $score -= 25;
                $reasons[] = 'low_gps_accuracy';
            } elseif ($acc !== null && $acc > 100) {
                $score -= 12;
            }
            // Accuracy reported as an impossibly perfect value can indicate a
            // mock-location provider feeding fixed coordinates.
            if ($acc !== null && $acc > 0 && $acc < 1) {
                $score -= 10;
                $reasons[] = 'suspicious_accuracy';
            }
        }

        // ── Where it was filed from ──────────────────────────────────────
        if ($visit->source === 'desktop') {
            $score -= 20;
            $reasons[] = 'filed_from_desktop';
        }
        $signals['source'] = $visit->source;

        // ── Photo evidence ───────────────────────────────────────────────
        if (! $visit->photo_path) {
            $score -= 10;
            $signals['photo'] = false;
        } else {
            $signals['photo'] = true;
            if ($visit->photo_taken_at && $visit->visited_at) {
                $gap = abs($visit->photo_taken_at->diffInMinutes($visit->visited_at));
                $signals['photo_time_gap_min'] = $gap;
                if ($gap > 120) {
                    $score -= 20;
                    $reasons[] = 'photo_time_mismatch';
                }
            }
        }

        // ── Dwell time (did they actually stay?) ─────────────────────────
        if ($visit->dwell_seconds !== null) {
            $signals['dwell_seconds'] = $visit->dwell_seconds;
            if ($visit->dwell_seconds < 90) {
                $score -= 20;
                $reasons[] = 'very_short_visit';
            }
        } else {
            // No check-out recorded — mild, not a flag (check-out is optional).
            $score -= 5;
            $signals['dwell_seconds'] = null;
        }

        // ── Impossible travel from the previous visit ────────────────────
        if ($previous && $previous->hasLocation() && $visit->hasLocation()
            && $previous->visited_at && $visit->visited_at) {
            $metres  = $this->haversine(
                (float) $previous->lat, (float) $previous->lng,
                (float) $visit->lat,    (float) $visit->lng,
            );
            $seconds = abs($visit->visited_at->getTimestamp() - $previous->visited_at->getTimestamp());
            if ($seconds > 0) {
                $kmh = ($metres / 1000) / ($seconds / 3600);
                $signals['travel_from_prev_kmh'] = round($kmh, 1);
                if ($kmh > self::MAX_PLAUSIBLE_KMH) {
                    $score -= 40;
                    $reasons[] = 'impossible_travel';
                }
            }
        }

        // ── Always logging from the same coordinates (e.g. from home) ─────
        if ($sameSpotPriors >= self::SAME_SPOT_LIMIT) {
            $score -= 25;
            $reasons[] = 'repeated_location';
            $signals['same_spot_priors'] = $sameSpotPriors;
        }

        $score   = max(0, min(100, $score));
        $flagged = $reasons !== [] || $score < 45;

        return [
            'score'   => $score,
            'flagged' => $flagged,
            'reasons' => array_values(array_unique($reasons)),
            'signals' => $signals,
        ];
    }

    /** Great-circle distance between two lat/lng points, in metres. */
    public function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000.0; // metres
        $dLat  = deg2rad($lat2 - $lat1);
        $dLng  = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /** Human labels (translation keys) for each flag reason — manager UI. */
    public static function reasonLabels(): array
    {
        return [
            'no_gps'              => 'Location was turned off',
            'no_location'        => 'No location captured',
            'low_gps_accuracy'   => 'Very low GPS accuracy',
            'suspicious_accuracy' => 'Suspicious GPS accuracy',
            'filed_from_desktop' => 'Filed from a desktop, not in the field',
            'photo_time_mismatch' => 'Photo time far from visit time',
            'very_short_visit'   => 'Stayed only a very short time',
            'impossible_travel'  => 'Impossible travel speed from previous visit',
            'repeated_location'  => 'Repeatedly logged from the same spot',
        ];
    }
}
