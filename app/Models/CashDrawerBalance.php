<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashDrawerBalance extends Model
{
    protected $fillable = [
        'cash_drawer_session_id',
        'currency',
        'opening_amount',
        'closing_amount',
        'expected_amount',
        'variance',
    ];

    protected $casts = [
        'opening_amount'  => 'decimal:2',
        'closing_amount'  => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'variance'        => 'decimal:2',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(CashDrawerSession::class, 'cash_drawer_session_id');
    }
}
