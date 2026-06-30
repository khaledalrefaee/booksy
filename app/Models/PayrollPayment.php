<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollPayment extends Model
{
    protected $fillable = [
        'company_id', 'employee_id', 'branch_id', 'branch_payment_id',
        'month', 'year', 'week_number', 'day', 'pay_period',
        'base_salary', 'commissions', 'deductions',
        'net_amount', 'currency', 'payment_method', 'notes', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'commissions' => 'decimal:2',
            'deductions'  => 'decimal:2',
            'net_amount'  => 'decimal:2',
            'paid_at'     => 'datetime',
        ];
    }

    public function employee(): BelongsTo { return $this->belongsTo(Employee::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function branchPayment(): BelongsTo { return $this->belongsTo(BranchPayment::class); }
}
