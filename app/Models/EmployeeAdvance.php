<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeAdvance extends Model
{
    protected $fillable = [
        'company_id',
        'employee_id',
        'branch_payment_id',
        'amount',
        'currency',
        'advance_date',
        'installments_count',
        'installment_amount',
        'payment_method',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount'             => 'decimal:2',
            'installment_amount' => 'decimal:2',
            'advance_date'       => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function branchPayment(): BelongsTo
    {
        return $this->belongsTo(BranchPayment::class);
    }

    /** Installment deduction rows generated for this advance. */
    public function installments(): HasMany
    {
        return $this->hasMany(EmployeeDeduction::class, 'advance_id')->orderBy('deduction_date');
    }

    /** Amount already collected: installments whose payroll month has started. */
    public function collectedAmount(): float
    {
        return (float) $this->installments
            ->filter(fn ($d) => $d->deduction_date->lte(now()->endOfMonth()))
            ->sum('amount');
    }

    public function remainingAmount(): float
    {
        return max(0, round((float) $this->amount - $this->collectedAmount(), 2));
    }

    public function isSettled(): bool
    {
        return $this->remainingAmount() <= 0;
    }

    /** 0-100 progress for the UI bar. */
    public function progressPct(): int
    {
        return $this->amount > 0
            ? (int) min(100, round($this->collectedAmount() / (float) $this->amount * 100))
            : 0;
    }
}
