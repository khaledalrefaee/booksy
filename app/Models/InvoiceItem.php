<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItem extends Model
{
    protected $fillable = [
        'invoice_id', 'type', 'description',
        'employee_name', 'customer_name',
        'unit_price', 'qty', 'discount_amount', 'total',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'unit_price'      => 'decimal:2',
            'qty'             => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total'           => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
