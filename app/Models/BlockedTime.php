<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BlockedTime extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'start_time',
        'end_time',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'start_time' => 'datetime',
            'end_time'   => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Blocks overlapping the [$start, $end) window. */
    public function scopeOverlapping($query, $start, $end)
    {
        return $query->where('start_time', '<', $end)->where('end_time', '>', $start);
    }

    /** Blocks that apply to the given employee (or branch-wide blocks). */
    public function scopeForEmployee($query, ?int $employeeId)
    {
        return $query->where(function ($q) use ($employeeId) {
            $q->whereNull('employee_id');
            if ($employeeId) {
                $q->orWhere('employee_id', $employeeId);
            }
        });
    }
}
