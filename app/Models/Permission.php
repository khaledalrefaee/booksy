<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $fillable = [
        'slug',
        'group',
        'level',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'permission_role');
    }

    public function isBranchLevel(): bool
    {
        return $this->level === 'branch';
    }

    public function isCompanyLevel(): bool
    {
        return $this->level === 'company';
    }
}
