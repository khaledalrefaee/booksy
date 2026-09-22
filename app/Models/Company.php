<?php

namespace App\Models;

use App\Models\Concerns\HasLocalizedNames;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Company extends Authenticatable
{
    use Notifiable;
    use HasFactory;
    use HasLocalizedNames;
    use SoftDeletes;   // deleted_at = إغلاق الحساب الاختياري (قابل للاستعادة من الأونر)

    protected $fillable = [
        'name_en',
        'name_ar',
        'owner_name',
        'email',
        'email_verified_at',
        'phone',
        'phone_verified_at',
        'logo',
        'category_id',
        'password',
        'status',
        'suspended_at',
        'suspension_reason',
        'submitted_for_review_at',
        'closure_reason',
        'plan_id',
        'plan_expires_at',
        'feature_overrides',
        'booking_policy_mode',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'suspended_at' => 'datetime',
            'submitted_for_review_at' => 'datetime',
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

    /**
     * The timezone used to anchor this company's day boundaries (reports,
     * the Daily Business Summary, scheduled sends).
     *
     * There is no per-company timezone column yet: the whole platform stores
     * and reads datetimes in one application timezone (config('app.timezone'),
     * currently Asia/Damascus). This accessor is the single place that answers
     * "what is this company's local time?", so the day a per-company setting is
     * introduced only this method changes — every caller keeps working.
     */
    public function timezone(): string
    {
        return config('app.timezone');
    }

    public function bookingPolicies(): HasMany
    {
        return $this->hasMany(BookingPolicy::class);
    }

    /**
     * Resolve the policy that actually applies to a booking.
     * Unified mode → always the company default. Per-branch mode →
     * the branch's own row, falling back to the company default.
     * Never returns null: fills gaps with BookingPolicy::defaults().
     */
    public function effectiveBookingPolicy(?Branch $branch = null): BookingPolicy
    {
        $company = $this->bookingPolicies()->whereNull('branch_id')->first();

        if ($this->booking_policy_mode === 'per_branch' && $branch) {
            $branchPolicy = $this->bookingPolicies()->where('branch_id', $branch->id)->first();
            if ($branchPolicy) {
                return $branchPolicy;
            }
        }

        return $company ?? new BookingPolicy(BookingPolicy::defaults());
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

    // ── SMS credit system ────────────────────────────────────────────────────

    /** Every wallet under this company (its branch wallets + the company pool). */
    public function smsWallets(): HasMany
    {
        return $this->hasMany(SmsWallet::class);
    }

    /** The shared company-level pool wallet (branch_id null), if one exists. */
    public function smsPoolWallet(): HasOne
    {
        return $this->hasOne(SmsWallet::class)->whereNull('branch_id');
    }

    public function smsMessages(): HasMany
    {
        return $this->hasMany(SmsMessage::class);
    }

    public function smsAutomationSettings(): HasMany
    {
        return $this->hasMany(SmsAutomationSetting::class);
    }

    public function smsTemplates(): HasMany
    {
        return $this->hasMany(SmsTemplate::class);
    }

    /** The primary branch — the auto-seeded head office, or the oldest branch. */
    public function headOffice(): ?Branch
    {
        return $this->branches()->where('is_head_office', true)->orderBy('id')->first()
            ?? $this->branches()->orderBy('id')->first();
    }

    /** Live on the public marketplace. */
    public function isPublished(): bool
    {
        return $this->status === 'active';
    }

    /** Account approved and fully operational. */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /** Awaiting platform review — created but not yet approved. */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /** Setup finished and the company asked to be reviewed, but not yet approved. */
    public function isAwaitingReview(): bool
    {
        return $this->submitted_for_review_at !== null && $this->status === 'pending';
    }

    /** Access revoked by the platform: login is blocked and sessions are killed. */
    public function isSuspended(): bool
    {
        return $this->status === 'suspended';
    }

    /** True once the owner has confirmed the account with the OTP code. */
    public function isVerified(): bool
    {
        return ! is_null($this->phone_verified_at);
    }

    /**
     * Owner-facing explanation shown wherever a suspended account is turned away
     * (login screen, mid-session logout). Appends the recorded reason if any.
     */
    public function suspendedNotice(): string
    {
        $base = __('Your account has been suspended. Please contact support.');

        return $this->suspension_reason
            ? $base.' '.__('Reason:').' '.$this->suspension_reason
            : $base;
    }

    /**
     * The company asks the platform to review & publish it. This never makes the
     * account live — approval stays the owner's decision (status → active). It
     * only records readiness so the owner can spot it. Returns false when a
     * required step is still missing.
     */
    public function submitForReview(): bool
    {
        if (! \App\Services\OnboardingService::canPublish($this)) {
            return false;
        }

        if ($this->submitted_for_review_at === null) {
            $this->update(['submitted_for_review_at' => now()]);
        }

        return true;
    }

    /**
     * Owner approval path: take the business live. Flips company → active and
     * clears the head-office marketplace gate (branch → active) in one step, so
     * approving a pending account is all it takes to appear publicly.
     */
    public function markActive(): void
    {
        $this->update(['status' => 'active']);

        $headOffice = $this->headOffice();
        if ($headOffice && $headOffice->isInactive()) {
            $headOffice->update(['status' => 'active']);
        }
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
