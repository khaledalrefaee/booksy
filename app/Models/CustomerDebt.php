<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomerDebt extends Model
{
    public const STATUSES = ['unpaid', 'partial', 'paid', 'waived'];

    protected $fillable = [
        'company_id', 'branch_id', 'customer_id',
        'appointment_id', 'invoice_id',
        'original_amount', 'paid_amount', 'currency',
        'status', 'notes', 'created_by_name',
        'due_date', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'due_date' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function getRemainingAttribute(): float
    {
        return round((float) $this->original_amount - (float) $this->paid_amount, 2);
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function recalculate(): void
    {
        $totalPaid = $this->payments()->sum('amount');
        $this->paid_amount = $totalPaid;

        if ($totalPaid >= (float) $this->original_amount) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($totalPaid > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }

        $this->save();
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo { return $this->belongsTo(Branch::class); }
    public function customer(): BelongsTo { return $this->belongsTo(Customer::class); }
    public function appointment(): BelongsTo { return $this->belongsTo(Appointment::class); }
    public function invoice(): BelongsTo { return $this->belongsTo(Invoice::class); }
    public function payments(): HasMany { return $this->hasMany(CustomerDebtPayment::class); }
}
