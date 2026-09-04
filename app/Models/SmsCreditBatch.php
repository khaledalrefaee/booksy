<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsCreditBatch extends Model
{
    protected $fillable = [
        'wallet_id', 'source', 'package_id', 'credits',
        'remaining', 'price', 'expires_at', 'note', 'created_by_owner_id',
    ];

    protected function casts(): array
    {
        return [
            'credits'    => 'integer',
            'remaining'  => 'integer',
            'price'      => 'decimal:2',
            'expires_at' => 'datetime',
        ];
    }

    public function wallet(): BelongsTo  { return $this->belongsTo(SmsWallet::class, 'wallet_id'); }
    public function package(): BelongsTo { return $this->belongsTo(SmsPackage::class); }

    /** Batches still holding credits and not past their expiry, oldest first (FIFO). */
    public function scopeConsumable(Builder $query): Builder
    {
        return $query->where('remaining', '>', 0)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->orderByRaw('expires_at is null')   // dated batches drain first
            ->orderBy('expires_at')
            ->orderBy('id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
