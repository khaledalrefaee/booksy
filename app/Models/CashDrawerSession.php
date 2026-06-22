<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDrawerSession extends Model
{
    public const RECONCILE_REASONS = [
        'logging_error'    => ['label_key' => 'Logging error',    'icon' => '📝', 'color' => '#f59e0b'],
        'change_error'     => ['label_key' => 'Change error',     'icon' => '💱', 'color' => '#fb923c'],
        'unexplained_shortage' => ['label_key' => 'Unexplained shortage', 'icon' => '🔍', 'color' => '#ef4444'],
        'rounding'         => ['label_key' => 'Rounding',         'icon' => '🔢', 'color' => '#22c55e'],
        'other'            => ['label_key' => 'Other',            'icon' => '📋', 'color' => '#64748b'],
    ];

    protected $fillable = [
        'company_id', 'branch_id', 'opened_by', 'closed_by',
        'opening_balance', 'closing_balance', 'expected_balance', 'variance',
        'currency', 'status', 'opened_at', 'closed_at', 'notes',
        'reconcile_reason', 'reconcile_notes', 'reconciled_by', 'reconciled_at',
    ];

    protected function casts(): array
    {
        return [
            'opening_balance'  => 'decimal:2',
            'closing_balance'  => 'decimal:2',
            'expected_balance' => 'decimal:2',
            'variance'         => 'decimal:2',
            'opened_at'        => 'datetime',
            'closed_at'        => 'datetime',
            'reconciled_at'    => 'datetime',
        ];
    }

    public function isOpen(): bool       { return $this->status === 'open'; }
    public function isClosed(): bool     { return $this->status === 'closed'; }
    public function isReconciled(): bool { return $this->status === 'reconciled'; }

    public function scopeOpen($query)    { return $query->where('status', 'open'); }

    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function openedBy(): BelongsTo { return $this->belongsTo(Employee::class, 'opened_by'); }
    public function closedBy(): BelongsTo     { return $this->belongsTo(Employee::class, 'closed_by'); }
    public function reconciledBy(): BelongsTo { return $this->belongsTo(Employee::class, 'reconciled_by'); }
}
