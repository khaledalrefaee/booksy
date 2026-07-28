<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;
    use HasLocalizedNames;

    /** Supported service classifications. */
    public const TYPES = ['standard', 'package', 'membership', 'addon', 'consultation'];

    /** Supported pricing models. */
    public const PRICE_TYPES = ['fixed', 'from', 'range'];

    /** Optional merchandising badges shown to customers. */
    public const BADGES = ['most_requested', 'new', 'special_offer', 'premium'];

    protected $fillable = [
        'branch_id',
        'service_category_id',
        'service_type',
        'name_en',
        'name_ar',
        'description',
        'price',
        'price_type',
        'price_to',
        'currency',
        'duration_minutes',
        'image_path',
        'is_active',
        'is_bookable_online',
        'is_popular',
        'is_recommended',
        'badges',
        'is_free',
        'requires_approval',
        'sort_order',
        'membership_validity_days',
        'membership_sessions',
        'discount_type',
        'discount_value',
        'discount_starts_at',
        'discount_ends_at',
    ];

    protected $attributes = [
        'service_type' => 'standard',
        'price_type'   => 'fixed',
    ];

    protected function casts(): array
    {
        return [
            'price'                    => 'decimal:2',
            'price_to'                 => 'decimal:2',
            'discount_value'           => 'decimal:2',
            'duration_minutes'         => 'integer',
            'is_active'                => 'boolean',
            'is_bookable_online'       => 'boolean',
            'is_popular'               => 'boolean',
            'is_recommended'           => 'boolean',
            'badges'                   => 'array',
            'is_free'                  => 'boolean',
            'requires_approval'        => 'boolean',
            'sort_order'               => 'integer',
            'membership_validity_days' => 'integer',
            'membership_sessions'      => 'integer',
            'discount_starts_at'       => 'datetime',
            'discount_ends_at'         => 'datetime',
        ];
    }

    public function isPackage(): bool     { return $this->service_type === 'package'; }
    public function isMembership(): bool  { return $this->service_type === 'membership'; }
    public function isConsultation(): bool{ return $this->service_type === 'consultation'; }
    public function isAddon(): bool       { return $this->service_type === 'addon'; }

    /** A package or membership bundles child services; both use the same pivot. */
    public function bundlesServices(): bool
    {
        return in_array($this->service_type, ['package', 'membership'], true);
    }

    /**
     * Human-facing price string that respects the pricing model, e.g.
     * "15,000", "from 25,000", "80,000 – 150,000". Currency is appended by the
     * caller so this stays reusable in tight table cells.
     */
    public function priceLabel(): string
    {
        $from = number_format((float) $this->price, 0);

        return match ($this->price_type) {
            'from'  => __('from') . ' ' . $from,
            'range' => $from . ' – ' . number_format((float) ($this->price_to ?? $this->price), 0),
            default => $from,
        };
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

    public function resources(): BelongsToMany
    {
        return $this->belongsToMany(Resource::class, 'resource_service');
    }

    /** Child services bundled by this package (only meaningful when isPackage()). */
    public function packageItems(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'package_service', 'package_id', 'child_service_id')
            ->withPivot(['is_optional', 'quantity', 'sort_order'])
            ->withTimestamps()
            ->orderBy('package_service.sort_order');
    }

    /** Packages that include this service as one of their items. */
    public function partOfPackages(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'package_service', 'child_service_id', 'package_id');
    }

    /** Add-on services offered alongside this (parent) service, e.g. Haircut → Hair mask. */
    public function addons(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_addon', 'parent_service_id', 'addon_service_id')
            ->withPivot('sort_order')->withTimestamps()->orderBy('service_addon.sort_order');
    }

    /** Parent services this add-on can be attached to (only meaningful when isAddon()). */
    public function parentServices(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'service_addon', 'addon_service_id', 'parent_service_id')
            ->withTimestamps();
    }
}
