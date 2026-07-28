<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingIdempotencyKey extends Model
{
    protected $fillable = [
        'company_id',
        'key',
        'response',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
