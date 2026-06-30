<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TreatmentPlanSession extends Model
{
    public const STATUSES = [
        'pending'   => ['label_key' => 'Pending',   'icon' => '⏳', 'color' => '#f59e0b'],
        'completed' => ['label_key' => 'Completed', 'icon' => '✅', 'color' => '#22c55e'],
        'skipped'   => ['label_key' => 'Skipped',   'icon' => '⏭️', 'color' => '#64748b'],
    ];

    protected $fillable = [
        'treatment_plan_id', 'appointment_id', 'session_number',
        'amount_due', 'amount_paid', 'currency', 'status', 'notes',
        'scheduled_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_due'   => 'decimal:2',
            'amount_paid'  => 'decimal:2',
            'scheduled_at' => 'date',
            'completed_at' => 'datetime',
        ];
    }

    public function plan(): BelongsTo       { return $this->belongsTo(TreatmentPlan::class, 'treatment_plan_id'); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }

    public function getRemainingAttribute(): float
    {
        return round((float) $this->amount_due - (float) $this->amount_paid, 2);
    }
}
