<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchPayment extends Model
{
    // direction derived from type: income/tip/other_income → in; expense/refund/adjustment → out
    public const INCOME_TYPES  = ['income', 'tip', 'refund'];
    public const EXPENSE_TYPES = ['expense', 'adjustment'];

    public const CATEGORIES = [
        'appointment'   => ['label_key' => 'Appointment income', 'icon' => '📅', 'color' => '#22c55e', 'type' => 'income'],
        'tip'           => ['label_key' => 'Tip',                'icon' => '💝', 'color' => '#f472b6', 'type' => 'income'],
        'other_income'  => ['label_key' => 'Other income',       'icon' => '➕', 'color' => '#34d399', 'type' => 'income'],
        'salary'        => ['label_key' => 'Salary payment',     'icon' => '💰', 'color' => '#a78bfa', 'type' => 'expense'],
        'product'       => ['label_key' => 'Products / Supplies','icon' => '📦', 'color' => '#fb923c', 'type' => 'expense'],
        'repair'        => ['label_key' => 'Repair / Maintenance','icon' => '🔧', 'color' => '#fbbf24', 'type' => 'expense'],
        'personal'      => ['label_key' => 'Personal / Home',    'icon' => '🏠', 'color' => '#60a5fa', 'type' => 'expense'],
        'other_expense' => ['label_key' => 'Other expense',      'icon' => '➖', 'color' => '#f87171', 'type' => 'expense'],
    ];

    public const PAYMENT_METHODS = [
        'cash'          => ['label_key' => 'Cash',          'icon' => '💵', 'color' => '#22c55e'],
        'card'          => ['label_key' => 'Card',          'icon' => '💳', 'color' => '#667eea'],
        'bank_transfer' => ['label_key' => 'Bank transfer', 'icon' => '🏦', 'color' => '#f59e0b'],
        'later'         => ['label_key' => 'Pay later',     'icon' => '⏳', 'color' => '#ef4444'],
    ];

    protected $fillable = [
        'company_id',
        'branch_id',
        'appointment_id',
        'type',
        'category',
        'amount',
        'currency',
        'payment_method',
        'reference',
        'notes',
        'recorded_by_employee_id',
        'paid_at',
    ];

    public function isIncome(): bool
    {
        $cat = $this->category ?? '';
        return isset(self::CATEGORIES[$cat]) && self::CATEGORIES[$cat]['type'] === 'income';
    }

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
