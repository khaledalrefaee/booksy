<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerNotification extends Model
{
    protected $fillable = [
        'company_id', 'type', 'title', 'body',
        'icon', 'color', 'link', 'data', 'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data'    => 'array',
            'read_at' => 'datetime',
        ];
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
