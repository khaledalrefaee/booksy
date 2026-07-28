<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformCoupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'type',
        'value',
        'currency',
        'company_ids',
        'plan_ids',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'value'       => 'decimal:2',
            'company_ids' => 'array',
            'plan_ids'    => 'array',
            'expires_at'  => 'date',
            'is_active'   => 'boolean',
        ];
    }

    public function isUsable(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return $this->max_uses === null || $this->used_count < $this->max_uses;
    }

    public function appliesTo(Company $company, Plan $plan): bool
    {
        if ($this->company_ids !== null && ! in_array($company->id, array_map('intval', $this->company_ids), true)) {
            return false;
        }

        if ($this->plan_ids !== null && ! in_array($plan->id, array_map('intval', $this->plan_ids), true)) {
            return false;
        }

        // A fixed-amount coupon only works when currencies match.
        if ($this->type === 'fixed' && $this->currency !== null && $this->currency !== $plan->currency) {
            return false;
        }

        return true;
    }

    public function discountFor(Plan $plan): float
    {
        $price = (float) $plan->price;

        $discount = $this->type === 'percent'
            ? $price * (float) $this->value / 100
            : (float) $this->value;

        return round(min($discount, $price), 2);
    }
}
