<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;
    protected $fillable = [
        'slug',
        'label_en',
        'label_ar',
        'description',
    ];

    /** @var array<int,string>|null Memoised default permission slugs (L1). */
    protected ?array $cachedPermissionSlugs = null;

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
    }

    /** Slugs this role grants by default (L1). Memoised per instance. */
    public function permissionSlugs(): array
    {
        if ($this->cachedPermissionSlugs === null) {
            $this->cachedPermissionSlugs = $this->relationLoaded('permissions')
                ? $this->permissions->pluck('slug')->all()
                : $this->permissions()->pluck('slug')->all();
        }

        return $this->cachedPermissionSlugs;
    }

    public function hasPermission(string $slug): bool
    {
        return in_array($slug, $this->permissionSlugs(), true);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function localizedName(): string
    {
        return app()->getLocale() === 'ar'
            ? ($this->label_ar ?: $this->label_en ?: '')
            : ($this->label_en ?: $this->label_ar ?: '');
    }
}
