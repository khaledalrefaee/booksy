<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeDeduction extends Model
{
    protected $fillable = [
        'employee_id',
        'recorded_by_employee_id',
        'type',
        'is_sick_leave',
        'deduction_date',
        'amount',
        'hours',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'is_sick_leave'  => 'boolean',
            'deduction_date' => 'date',
            'amount'         => 'decimal:2',
            'hours'          => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recorded_by_employee_id');
    }

    /** True deductions (not sick leave) */
    public function scopeDeductible($query)
    {
        return $query->where('is_sick_leave', false);
    }
}
