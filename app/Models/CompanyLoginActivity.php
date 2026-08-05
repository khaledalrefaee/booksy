<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyLoginActivity extends Model
{
    public const UPDATED_AT = null; // append-only: created_at only

    protected $fillable = [
        'company_id', 'email_attempted', 'successful', 'ip', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'successful' => 'boolean',
        ];
    }

    public function scopeSuccessful($query)
    {
        return $query->where('successful', true);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
