<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'invoice_number', 'company_id', 'branch_id',
        'booking_group_id', 'appointment_id',
        'customer_name', 'customer_phone', 'customer_email',
        'currency', 'subtotal', 'discount_amount',
        'vat_rate', 'vat_amount', 'total', 'amount_paid',
        'payment_method', 'status', 'notes',
        'issued_at', 'paid_at',
        'created_by_type', 'created_by_id', 'created_by_name',
    ];

    protected function casts(): array
    {
        return [
            'subtotal'        => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'vat_rate'        => 'decimal:2',
            'vat_amount'      => 'decimal:2',
            'total'           => 'decimal:2',
            'issued_at'       => 'datetime',
            'paid_at'         => 'datetime',
        ];
    }

    public static function generateNumber(int $companyId): string
    {
        $prefix = 'INV-' . now()->format('Ymd');
        $lastNumber = static::where('company_id', $companyId)
            ->where('invoice_number', 'like', $prefix . '-%')
            ->lockForUpdate()
            ->max(\Illuminate\Support\Facades\DB::raw("CAST(SUBSTRING_INDEX(invoice_number, '-', -1) AS UNSIGNED)"));

        return $prefix . '-' . str_pad(($lastNumber ?? 0) + 1, 4, '0', STR_PAD_LEFT);
    }

    public function recalculate(): void
    {
        $subtotal = $this->items->sum(fn($i) => (float) $i->total);
        $vatAmount = round($subtotal * ((float) $this->vat_rate / 100), 2);
        $this->subtotal   = $subtotal;
        $this->vat_amount = $vatAmount;
        $this->total      = round($subtotal - (float) $this->discount_amount + $vatAmount, 2);
        $this->save();
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

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('sort_order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function getRemainingAttribute(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function recordPayment(float $amount, string $method = 'cash', ?string $notes = null, ?string $byName = null): InvoicePayment
    {
        $payment = $this->payments()->create([
            'amount' => $amount,
            'payment_method' => $method,
            'notes' => $notes,
            'recorded_by_name' => $byName,
        ]);

        $totalPaid = $this->payments()->sum('amount');
        $this->amount_paid = $totalPaid;

        if ($totalPaid >= (float) $this->total) {
            $this->status = 'paid';
            $this->paid_at = now();
        } elseif ($totalPaid > 0) {
            $this->status = 'partial';
        }

        $this->save();

        return $payment;
    }

    public function statusBadge(): string
    {
        return match ($this->status) {
            'paid'     => 'success',
            'issued'   => 'primary',
            'partial'  => 'warning',
            'void'     => 'danger',
            'refunded' => 'info',
            default    => 'secondary',
        };
    }
}
