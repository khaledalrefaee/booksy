<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SmsWallet extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'balance',
        'total_purchased', 'total_used',
        'low_balance_threshold', 'notify_low_balance',
        'notified_low_at', 'notified_zero_at',
    ];

    protected function casts(): array
    {
        return [
            'balance'               => 'integer',
            'total_purchased'       => 'integer',
            'total_used'            => 'integer',
            'low_balance_threshold' => 'integer',
            'notify_low_balance'    => 'boolean',
            'notified_low_at'       => 'datetime',
            'notified_zero_at'      => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }

    public function batches(): HasMany      { return $this->hasMany(SmsCreditBatch::class, 'wallet_id'); }
    public function transactions(): HasMany { return $this->hasMany(SmsTransaction::class, 'wallet_id'); }
    public function messages(): HasMany     { return $this->hasMany(SmsMessage::class, 'wallet_id'); }

    /** True = a company-level pool (shared fallback for the company's branches). */
    public function isCompanyPool(): bool
    {
        return $this->branch_id === null;
    }

    public function remaining(): int
    {
        return max(0, (int) $this->balance);
    }

    public function hasCredits(int $needed = 1): bool
    {
        return $this->remaining() >= $needed;
    }

    /** At or below the owner-defined low-balance line (but not empty). */
    public function isLow(): bool
    {
        return $this->remaining() > 0 && $this->remaining() <= $this->low_balance_threshold;
    }
}
