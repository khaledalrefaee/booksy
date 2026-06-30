<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TreatmentPlan extends Model
{
    public const STATUSES = [
        'active'    => ['label_key' => 'Active',    'icon' => '🟢', 'color' => '#22c55e'],
        'completed' => ['label_key' => 'Completed', 'icon' => '✅', 'color' => '#667eea'],
        'cancelled' => ['label_key' => 'Cancelled', 'icon' => '❌', 'color' => '#ef4444'],
    ];

    protected $fillable = [
        'customer_id', 'branch_id', 'title', 'total_amount', 'currency', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'total_amount' => 'decimal:2',
        ];
    }

    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function branch(): BelongsTo   { return $this->belongsTo(Branch::class); }
    public function sessions(): HasMany   { return $this->hasMany(TreatmentPlanSession::class); }

    public function getDebtAttribute(): float
    {
        return round(
            $this->sessions->where('status', '!=', 'skipped')->sum(fn($s) => $s->amount_due - $s->amount_paid),
            2
        );
    }

    public function getPaidAttribute(): float
    {
        return round($this->sessions->sum('amount_paid'), 2);
    }

    public function isActive(): bool    { return $this->status === 'active'; }
    public function isCompleted(): bool { return $this->status === 'completed'; }
}
