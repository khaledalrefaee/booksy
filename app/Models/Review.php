<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Review extends Model
{
    protected $fillable = [
        'branch_id',
        'appointment_id',
        'customer_id',
        'rating',
        'comment',
        'is_hidden',
        'hidden_reason',
        'reviewable_type',
        'reviewable_id',
    ];

    protected function casts(): array
    {
        return [
            'rating'    => 'integer',
            'is_hidden' => 'boolean',
        ];
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function reviewable(): MorphTo
    {
        return $this->morphTo();
    }
}
