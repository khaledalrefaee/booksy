<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Customer extends Model
{
    protected $fillable = [
        'name', 'phone', 'age', 'avatar', 'phone_verified_at',
    ];

    protected function casts(): array
    {
        return [
            'phone_verified_at' => 'datetime',
            'age'               => 'integer',
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
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
