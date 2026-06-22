<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasLocalizedNames;

    protected $fillable = [
        'branch_id',
        'service_category_id',
        'name_en',
        'name_ar',
        'description',
        'price',
        'currency',
        'duration_minutes',
        'is_active',
        'discount_type',
        'discount_value',
        'discount_starts_at',
        'discount_ends_at',
    ];

    protected function casts(): array
    {
        return [
            'price'              => 'decimal:2',
            'discount_value'     => 'decimal:2',
            'duration_minutes'   => 'integer',
            'is_active'          => 'boolean',
            'discount_starts_at' => 'datetime',
            'discount_ends_at'   => 'datetime',
        ];
    }

    /** Whether a discount is configured and currently active */
    public function hasActiveDiscount(): bool
    {
        if (! $this->discount_type || ! $this->discount_value) {
            return false;
        }
        $now = now();
        if ($this->discount_starts_at && $now->lt($this->discount_starts_at)) {
            return false;
        }
        if ($this->discount_ends_at && $now->gt($this->discount_ends_at)) {
            return false;
        }
        return true;
    }

    /** Final price after applying the active discount */
    public function finalPrice(): float
    {
        if (! $this->hasActiveDiscount()) {
            return (float) $this->price;
        }
        if ($this->discount_type === 'percent') {
            $discounted = (float) $this->price * (1 - (float) $this->discount_value / 100);
        } else {
            $discounted = (float) $this->price - (float) $this->discount_value;
        }
        return max(0, $discounted);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function serviceCategory(): BelongsTo
    {
        return $this->belongsTo(ServiceCategory::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    public function employees(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'employee_service');
    }
}
