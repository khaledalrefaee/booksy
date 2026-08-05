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

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_role');
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
