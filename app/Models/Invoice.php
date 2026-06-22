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
        'vat_rate', 'vat_amount', 'total',
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
        $last = static::where('company_id', $companyId)
            ->where('invoice_number', 'like', $prefix . '%')
            ->count();
        return $prefix . '-' . str_pad($last + 1, 4, '0', STR_PAD_LEFT);
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
