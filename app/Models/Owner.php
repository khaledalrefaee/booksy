<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Owner extends Authenticatable
{
    use Notifiable;

    protected $fillable = [
        'name',
        'email',
        'role',
        'phone',
        'avatar',
        'password',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }

    public function hasPermission(string $key): bool
    {
        $permissions = config('owner-permissions.roles.'.$this->role, []);

        return in_array('*', $permissions, true) || in_array($key, $permissions, true);
    }
}
