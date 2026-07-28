<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeLeave extends Model
{
    public const LEAVE_TYPES = [
        'annual' => ['label_key' => 'Annual leave', 'icon' => '🏖️', 'color' => '#667eea'],
        'sick'   => ['label_key' => 'Sick leave',   'icon' => '🤒', 'color' => '#f59e0b'],
        'unpaid' => ['label_key' => 'Unpaid leave', 'icon' => '💸', 'color' => '#94a3b8'],
        'other'  => ['label_key' => 'Other leave',  'icon' => '📌', 'color' => '#f093fb'],
    ];

    protected $fillable = [
        'employee_id',
        'company_id',
        'start_date',
        'end_date',
        'type',
        'is_hourly',
        'start_hour',
        'end_hour',
        'reason',
        'status',
        'notes',
        'deduction_amount',
        'deduction_currency',
        'deduction_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date'       => 'date',
            'end_date'         => 'date',
            'is_hourly'        => 'boolean',
            'deduction_amount' => 'decimal:2',
        ];
    }

    /** The salary deduction created when this leave was approved (if any). */
    public function deduction(): BelongsTo
    {
        return $this->belongsTo(EmployeeDeduction::class, 'deduction_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function daysCount(): int
    {
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /** Duration in hours for hourly (permission) leaves. */
    public function hoursCount(): float
    {
        if (! $this->is_hourly || ! $this->start_hour || ! $this->end_hour) {
            return 0;
        }

        $mins = \Carbon\Carbon::parse($this->start_hour)->diffInMinutes(\Carbon\Carbon::parse($this->end_hour));

        return round($mins / 60, 1);
    }

    public function typeMeta(): array
    {
        return self::LEAVE_TYPES[$this->type] ?? self::LEAVE_TYPES['other'];
    }

    /** Days of this leave that fall inside the given calendar year. */
    public function daysInYear(int $year): int
    {
        $yearStart = \Carbon\Carbon::create($year, 1, 1)->startOfDay();
        $yearEnd   = \Carbon\Carbon::create($year, 12, 31)->endOfDay();

        $from = $this->start_date->greaterThan($yearStart) ? $this->start_date : $yearStart;
        $to   = $this->end_date->lessThan($yearEnd) ? $this->end_date : $yearEnd;

        if ($from->greaterThan($to)) {
            return 0;
        }

        return (int) $from->diffInDays($to) + 1;
    }
}
