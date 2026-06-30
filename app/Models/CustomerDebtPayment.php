<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerDebtPayment extends Model
{
    protected $fillable = [
        'customer_debt_id', 'amount', 'payment_method',
        'notes', 'recorded_by_name',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    public function debt(): BelongsTo
    {
        return $this->belongsTo(CustomerDebt::class, 'customer_debt_id');
    }
}
