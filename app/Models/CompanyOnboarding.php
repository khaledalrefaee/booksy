<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CompanyOnboarding extends Model
{
    protected $table = 'company_onboarding';

    protected $fillable = [
        'company_id', 'tour_completed_at', 'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'tour_completed_at' => 'datetime',
            'dismissed_at'      => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
