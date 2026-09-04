<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SmsAutomationSetting extends Model
{
    protected $fillable = [
        'company_id', 'branch_id',
        'confirmation_enabled',
        'reminder_enabled', 'reminder_offset_minutes',
        'followup_enabled', 'followup_days',
    ];

    protected function casts(): array
    {
        return [
            'confirmation_enabled'    => 'boolean',
            'reminder_enabled'        => 'boolean',
            'reminder_offset_minutes' => 'integer',
            'followup_enabled'        => 'boolean',
            'followup_days'           => 'integer',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }

    /** Is the automation for a given message type switched on for this branch? */
    public function enabledFor(string $type): bool
    {
        return match ($type) {
            'confirmation' => (bool) $this->confirmation_enabled,
            'reminder'     => (bool) $this->reminder_enabled,
            'followup'     => (bool) $this->followup_enabled,
            default        => false,
        };
    }
}
