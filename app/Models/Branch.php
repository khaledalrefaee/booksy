<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Branch extends Model
{
    use HasLocalizedNames;

    protected static function booted(): void
    {
        static::creating(function (Branch $branch) {
            if (empty($branch->slug)) {
                $branch->slug = $branch->generateSlug();
            }
        });

        static::updating(function (Branch $branch) {
            if ($branch->isDirty('name_en') || empty($branch->slug)) {
                $branch->slug = $branch->generateSlug();
            }
        });
    }

    protected $fillable = [
        'company_id',
        'name_en',
        'name_ar',
        'sort_order',
        'is_head_office',
        'status',
        'booking_mode',
        'slug',
        'phone',
        'phones',
        'address',
        'description_en',
        'description_ar',
        'country_id',
        'governorate_id',
        'area_id',
        'latitude',
        'longitude',
        'landline_phone',
        'landlines',
        'qr_code',
        'overpayment_to',
    ];

    public function isMarketplace(): bool { return $this->booking_mode === 'marketplace'; }
    public function isPrivate(): bool     { return $this->booking_mode === 'private'; }

    public function scopeMarketplace($query)
    {
        return $query->where('booking_mode', '!=', 'private');
    }

    public function generateSlug(): string
    {
        $base = Str::slug($this->name_en ?: $this->name_ar ?: 'branch');
        $slug = $base;
        $i = 1;
        while (static::where('slug', $slug)->where('id', '!=', $this->id ?? 0)->exists()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    public function privateBookingUrl(): string
    {
        return url('/s/' . $this->slug);
    }

    // Convenience helpers
    public function isActive(): bool    { return $this->status === 'active'; }
    public function isInactive(): bool  { return $this->status === 'inactive'; }
    public function isMaintenance(): bool { return $this->status === 'maintenance'; }

    public function statusLabel(): string
    {
        return match($this->status) {
            'active'      => 'Active',
            'inactive'    => 'Inactive',
            'maintenance' => 'Maintenance',
            default       => 'Unknown',
        };
    }

    public function statusColor(): string
    {
        return match($this->status) {
            'active'      => 'success',
            'inactive'    => 'secondary',
            'maintenance' => 'warning',
            default       => 'secondary',
        };
    }

    protected function casts(): array
    {
        return [
            'sort_order'     => 'integer',
            'is_head_office' => 'boolean',
            'latitude'       => 'decimal:8',
            'longitude'      => 'decimal:8',
            'phones'         => 'array',
            'landlines'      => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function governorate(): BelongsTo
    {
        return $this->belongsTo(Governorate::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function fullAddress(): string
    {
        $parts = array_filter([
            $this->area?->localizedName(),
            $this->governorate?->localizedName(),
            $this->country?->localizedName(),
            $this->address,
        ]);
        return implode('، ', $parts);
    }

    public function workingHours(): HasMany
    {
        return $this->hasMany(BranchWorkingHour::class);
    }

    public function services(): HasMany
    {
        return $this->hasMany(Service::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function waitlistEntries(): HasMany
    {
        return $this->hasMany(WaitlistEntry::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function branchPayments(): HasMany
    {
        return $this->hasMany(BranchPayment::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function socialLinks(): MorphMany
    {
        return $this->morphMany(SocialLink::class, 'linkable');
    }

    public function images(): HasMany
    {
        return $this->hasMany(BranchImage::class)->orderBy('sort_order');
    }
}
