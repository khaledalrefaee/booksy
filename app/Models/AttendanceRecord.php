<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    protected $fillable = [
        'employee_id', 'branch_id', 'company_id', 'date',
        'check_in', 'check_out', 'scheduled_start', 'scheduled_end',
        'status', 'check_in_lat', 'check_in_lng', 'check_in_distance',
        'check_out_lat', 'check_out_lng', 'check_out_distance',
        'location_status', 'notes', 'late_minutes',
    ];

    protected function casts(): array
    {
        return [
            'date'              => 'date',
            'check_in'          => 'datetime',
            'check_out'         => 'datetime',
            'check_in_lat'      => 'decimal:8',
            'check_in_lng'      => 'decimal:8',
            'check_out_lat'     => 'decimal:8',
            'check_out_lng'     => 'decimal:8',
            'check_in_distance' => 'integer',
            'check_out_distance'=> 'integer',
            'late_minutes'      => 'integer',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function company(): BelongsTo  { return $this->belongsTo(Company::class); }

    public static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return $r * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function locationStatus(float $distance): string
    {
        if ($distance <= 200) return 'inside';
        if ($distance <= 500) return 'nearby';
        return 'outside';
    }
}
