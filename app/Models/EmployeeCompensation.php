<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeCompensation extends Model
{
    protected $table = 'employee_compensations';

    protected $fillable = [
        'employee_id',
        'type',
        'base_amount',
        'currency',
        'pay_period',
        'commission_type',
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'base_amount'     => 'decimal:2',
            'commission_rate' => 'decimal:2',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** Per-service commission rows (used when commission_type = 'per_service') */
    public function serviceCommissions(): HasMany
    {
        return $this->hasMany(EmployeeServiceCommission::class, 'employee_id', 'employee_id');
    }
}
