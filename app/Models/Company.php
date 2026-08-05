<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Company extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use HasLocalizedNames;

    protected $fillable = [
        'name_en',
        'name_ar',
        'owner_name',
        'email',
        'email_verified_at',
        'phone',
        'logo',
        'category_id',
        'password',
        'status',
        'plan_id',
        'plan_expires_at',
        'feature_overrides',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'plan_expires_at' => 'date',
            'feature_overrides' => 'array',
        ];
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** Null expiry means the subscription never expires (free forever / manual). */
    public function isSubscriptionActive(): bool
    {
        if ($this->plan_expires_at === null) {
            return true;
        }

        // Plan's grace period keeps paid features on for a few days past expiry.
        $graceDays = $this->plan?->grace_days ?? 0;

        return ! $this->plan_expires_at->addDays($graceDays)->isPast();
    }

    public function subscriptionPayments(): HasMany
    {
        return $this->hasMany(SubscriptionPayment::class);
    }

    /**
     * Feature resolution order:
     *  1. per-company override (owner panel) always wins;
     *  2. no plan assigned → legacy company, everything enabled;
     *  3. expired subscription → gated features off;
     *  4. otherwise the plan decides.
     */
    public function hasFeature(string $key): bool
    {
        $override = $this->feature_overrides[$key] ?? null;
        if ($override !== null) {
            return (bool) $override;
        }

        if ($this->plan_id === null) {
            return true;
        }

        if (! $this->isSubscriptionActive()) {
            return false;
        }

        return $this->plan?->hasFeature($key) ?? false;
    }

    /** Null = unlimited. */
    public function maxBranches(): ?int
    {
        return $this->plan_id === null ? null : $this->plan?->max_branches;
    }

    /** Null = unlimited. */
    public function maxEmployees(): ?int
    {
        return $this->plan_id === null ? null : $this->plan?->max_employees;
    }

    public function canAddBranch(): bool
    {
        $max = $this->maxBranches();

        return $max === null || $this->branches()->count() < $max;
    }

    public function canAddEmployee(): bool
    {
        $max = $this->maxEmployees();

        return $max === null || $this->employees()->count() < $max;
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
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

    public function branchPayments(): HasMany
    {
        return $this->hasMany(BranchPayment::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function onboarding(): HasOne
    {
        return $this->hasOne(CompanyOnboarding::class);
    }

    public function loginActivities(): HasMany
    {
        return $this->hasMany(CompanyLoginActivity::class);
    }

    public function socialLinks(): MorphMany
    {
        return $this->morphMany(SocialLink::class, 'linkable');
    }

    public function serviceCategories(): HasMany
    {
        return $this->hasMany(ServiceCategory::class);
    }
}
