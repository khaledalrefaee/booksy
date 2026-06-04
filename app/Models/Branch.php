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
        'phone',
        'address',
        'latitude',
        'longitude',
        'landline_phone',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_head_office' => 'boolean',
            'latitude' => 'decimal:8',
            'longitude' => 'decimal:8',
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
}
