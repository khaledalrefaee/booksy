<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit + idempotency record for the Daily Business Summary email.
 * See the create_daily_summary_logs migration for why it exists.
 */
class DailySummaryLog extends Model
{
    protected $fillable = [
        'company_id',
        'summary_date',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'summary_date' => 'date',
            'sent_at'      => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
