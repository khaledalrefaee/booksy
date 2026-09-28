<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-branch "Customer messages" settings — the ONE place that decides which
 * automatic messages a customer gets, on either channel (SMS for local
 * numbers, from the branch's credits; WhatsApp for everyone else).
 * The table keeps its historical sms_* name.
 */
class SmsAutomationSetting extends Model
{
    protected $fillable = [
        'company_id', 'branch_id',
        'confirmation_enabled',
        'reminder_enabled', 'reminder_offset_minutes',
        'ask_confirmation',
        'followup_enabled', 'followup_days',
    ];

    /** Reminder lead times offered in the UI (minutes). */
    public const REMINDER_OFFSETS = [10, 15, 30, 60, 120, 180, 360, 720, 1440];

    protected function casts(): array
    {
        return [
            'confirmation_enabled'    => 'boolean',
            'reminder_enabled'        => 'boolean',
            'reminder_offset_minutes' => 'integer',
            'ask_confirmation'        => 'boolean',
            'followup_enabled'        => 'boolean',
            'followup_days'           => 'integer',
        ];
    }

    /** What a branch gets before anyone touches its settings. */
    public static function defaults(): array
    {
        return [
            'confirmation_enabled'    => true,
            'reminder_enabled'        => true,
            'reminder_offset_minutes' => 60,
            'ask_confirmation'        => true,
            'followup_enabled'        => false,
            'followup_days'           => 15,
        ];
    }

    /** The branch's saved settings, or the defaults (unsaved) when it has none. */
    public static function forBranch(int $companyId, ?int $branchId): self
    {
        $row = $branchId
            ? static::where('company_id', $companyId)->where('branch_id', $branchId)->first()
            : null;

        return $row ?? new static(['company_id' => $companyId, 'branch_id' => $branchId] + static::defaults());
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
