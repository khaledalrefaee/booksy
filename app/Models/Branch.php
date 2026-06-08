<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Branch extends Model
{
    use HasLocalizedNames;

    protected $fillable = [
        'company_id',
        'name_en',
        'name_ar',
        'sort_order',
        'is_head_office',
        'status',
        'phone',
        'address',
        'latitude',
        'longitude',
        'landline_phone',
    ];

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
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
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
