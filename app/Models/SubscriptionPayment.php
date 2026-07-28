<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SubscriptionPayment extends Model
{
    /** @return array<string, string> */
    public static function methods(): array
    {
        return [
            'cash'          => __('Cash'),
            'bank_transfer' => __('Bank transfer'),
            'sham_cash'     => __('Sham Cash'),
            'other'         => __('Other'),
        ];
    }

    protected $fillable = [
        'company_id',
        'company_label',
        'plan_id',
        'plan_label',
        'owner_id',
        'amount',
        'currency',
        'method',
        'reference',
        'paid_at',
        'expires_before',
        'plan_id_before',
        'expires_after',
        'notes',
        'coupon_id',
        'coupon_code',
        'list_price',
        'discount_amount',
        'voided_at',
        'void_reason',
        'voided_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'          => 'decimal:2',
            'list_price'      => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'paid_at'         => 'date',
            'expires_before'  => 'date',
            'expires_after'   => 'date',
            'voided_at'       => 'datetime',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }

    public function isVoided(): bool
    {
        return $this->voided_at !== null;
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(Owner::class, 'voided_by');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }
}
