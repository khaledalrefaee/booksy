<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * A GlowRez platform account: super admins AND internal staff
 * (field sales, sales managers, marketing, support). All authenticate
 * through the `owner` guard; what they can do is decided by permissions,
 * not by which table they live in.
 */
class Owner extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'phone',
        'avatar',
        'password',
        'is_active',
        'disabled_at',
        'permissions',
        'must_change_password',
        'created_by',
        'last_login_at',
        'last_activity_at',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'             => 'hashed',
            'permissions'          => 'array',
            'is_active'            => 'boolean',
            'must_change_password' => 'boolean',
            'disabled_at'          => 'datetime',
            'last_login_at'        => 'datetime',
            'last_activity_at'     => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    /** All permission keys this account effectively holds: role set ∪ overrides. */
    public function effectivePermissions(): array
    {
        $role = (array) config('owner-permissions.roles.'.$this->role, []);

        if (in_array('*', $role, true)) {
            return ['*'];
        }

        return array_values(array_unique(array_merge($role, (array) ($this->permissions ?? []))));
    }

    public function hasPermission(string $key): bool
    {
        $perms = $this->effectivePermissions();

        return in_array('*', $perms, true) || in_array($key, $perms, true);
    }

    public function isSuperAdmin(): bool
    {
        return in_array('*', (array) config('owner-permissions.roles.'.$this->role, []), true);
    }

    /** Can this account open the full /owner admin panel? */
    public function canAccessOwnerDashboard(): bool
    {
        return $this->hasPermission('owner-dashboard.view');
    }

    /** Does this account have any field-sales tool? (drives the /employee area) */
    public function canUseFieldTools(): bool
    {
        return $this->hasPermission('field-visits.create')
            || $this->hasPermission('field-visits.view.own');
    }

    /**
     * Where this account should land after login. Owner-dashboard users go to
     * the admin panel; field reps go to their own /employee home; anyone else
     * falls back to their profile (always reachable).
     */
    public function homeRoute(): string
    {
        if ($this->canAccessOwnerDashboard()) {
            return 'owner.dashboard';
        }

        if ($this->canUseFieldTools()) {
            return 'employee.home';
        }

        return 'owner.profile';
    }

    /*
    |--------------------------------------------------------------------------
    | Role helpers
    |--------------------------------------------------------------------------
    */

    public function roleMeta(): array
    {
        return config('owner-permissions.role_meta.'.$this->role, [
            'label' => $this->role,
            'desc'  => '',
            'icon'  => 'user',
        ]);
    }

    public function roleLabel(): string
    {
        return __($this->roleMeta()['label'] ?? $this->role);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function creator(): BelongsTo
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function createdEmployees(): HasMany
    {
        return $this->hasMany(self::class, 'created_by');
    }

    public function fieldVisits(): HasMany
    {
        return $this->hasMany(FieldVisit::class, 'owner_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** Everyone except super admins — the "employees" the team manages. */
    public function scopeEmployees($query)
    {
        return $query->where('role', '!=', 'super_admin');
    }
}
