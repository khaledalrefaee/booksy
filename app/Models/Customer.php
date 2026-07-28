<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\CustomerTier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends Model
{
    use HasFactory;
    public const TAGS = [
        'vip'     => ['label_key' => 'VIP',      'icon' => '⭐', 'color' => '#C9A227'],
        'regular' => ['label_key' => 'Regular',   'icon' => '👤', 'color' => '#667eea'],
        'new'     => ['label_key' => 'New',        'icon' => '🆕', 'color' => '#22c55e'],
        'loyal'   => ['label_key' => 'Loyal',      'icon' => '💎', 'color' => '#a78bfa'],
    ];

    public const SOURCES = [
        'instagram'       => ['label_key' => 'Instagram',       'icon' => '📸', 'color' => '#E4405F'],
        'facebook'        => ['label_key' => 'Facebook',        'icon' => '📘', 'color' => '#1877F2'],
        'google'          => ['label_key' => 'Google',          'icon' => '🔍', 'color' => '#4285F4'],
        'friend_referral' => ['label_key' => 'Friend referral', 'icon' => '👥', 'color' => '#22c55e'],
        'walk_in'         => ['label_key' => 'Walk-in',         'icon' => '🚶', 'color' => '#f59e0b'],
        'website'         => ['label_key' => 'Website',         'icon' => '🌐', 'color' => '#667eea'],
        'other'           => ['label_key' => 'Other',           'icon' => '📋', 'color' => '#64748b'],
    ];

    protected $fillable = [
        'name', 'phone', 'age', 'avatar', 'phone_verified_at', 'notes',
        'tag', 'is_banned', 'ban_reason', 'banned_at',
        'date_of_birth', 'source', 'loyalty_points',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'banned_at'         => 'datetime',
            'date_of_birth'     => 'date',
            'age'               => 'integer',
            'is_banned'         => 'boolean',
            'loyalty_points'    => 'integer',
        ];
    }

    public function isBanned(): bool { return $this->is_banned; }

    public function isBirthday(): bool
    {
        return $this->date_of_birth && $this->date_of_birth->isBirthday();
    }

    public function getCalculatedAgeAttribute(): ?int
    {
        return $this->date_of_birth ? $this->date_of_birth->age : $this->attributes['age'] ?? null;
    }

    public function isInactive(int $days = null): bool
    {
        $days = $days ?? config('booksy.miss_you_days', 30);
        $lastVisit = $this->last_visit ?? $this->appointments()->max('start_time');
        if (!$lastVisit) return false;
        return Carbon::parse($lastVisit)->lt(now()->subDays($days));
    }

    public function getDebtAttribute(): float
    {
        $treatmentDebt = $this->treatmentPlans->where('status', 'active')->sum(fn($p) => $p->debt);
        $cashDebt = $this->debts->whereIn('status', ['unpaid', 'partial'])->sum(fn($d) => $d->remaining);
        return round($treatmentDebt + $cashDebt, 2);
    }

    // ── Relationships ────────────────────────────────────────────────────
    public function appointments(): HasMany     { return $this->hasMany(Appointment::class); }

    /**
     * The badge reception sees: VIP / Loyal / Regular / New.
     *
     * Reads `visits_count` when the query loaded it — callers listing customers
     * must use withCount('appointments as visits_count') or this falls back to a
     * per-row query. The tag column wins when the salon set one.
     */
    public function tier(): CustomerTier
    {
        $visits = $this->visits_count ?? $this->appointments()
            ->where('status', AppointmentStatus::Completed->value)
            ->count();

        return CustomerTier::resolve($this->tag, (int) $visits);
    }
    public function branchNotes(): HasMany      { return $this->hasMany(CustomerBranchNote::class); }
    public function treatmentPlans(): HasMany   { return $this->hasMany(TreatmentPlan::class); }
    public function loyaltyPointLogs(): HasMany { return $this->hasMany(LoyaltyPointLog::class); }
    public function communications(): HasMany   { return $this->hasMany(CustomerCommunication::class); }
    public function reviews(): HasMany          { return $this->hasMany(Review::class); }
    public function debts(): HasMany            { return $this->hasMany(CustomerDebt::class); }

    public function branchNote(int $branchId): ?CustomerBranchNote
    {
        return $this->branchNotes->firstWhere('branch_id', $branchId);
    }

    public function linkedBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'customer_branches')
                    ->withPivot('created_at');
    }

    public function favoriteBranches(): BelongsToMany
    {
        return $this->belongsToMany(Branch::class, 'customer_favorites')
                    ->withTimestamps()
                    ->orderByPivot('created_at', 'desc');
    }

    public function hasFavorite(int $branchId): bool
    {
        return $this->favoriteBranches()->where('branch_id', $branchId)->exists();
    }
}
