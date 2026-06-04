<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchPayment extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'appointment_id',
        'type',
        'amount',
        'currency',
        'payment_method',
        'reference',
        'notes',
        'recorded_by_employee_id',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
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

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'recorded_by_employee_id');
    }
}
