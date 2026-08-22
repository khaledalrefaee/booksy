<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPolicy extends Model
{
    protected $fillable = [
        'company_id',
        'branch_id',
        'cancellation_window_hours',
        'late_grace_minutes',
        'late_action',
        'reminder_channel',
        'reminder_on_booking',
        'reminder_24h',
        'reminder_3h',
        'require_confirmation',
        'confirmation_deadline_hours',
        'protection_enabled',
        'offense_threshold',
        'offense_window_days',
        'action_alert_staff',
        'action_manual_confirm',
        'deposit_enabled',
        'deposit_type',
        'deposit_amount',
        'deposit_scope',
        'msg_confirm',
        'msg_reminder_24h',
        'msg_reminder_3h',
        'msg_unconfirmed',
    ];

    protected function casts(): array
    {
        return [
            'cancellation_window_hours'   => 'integer',
            'late_grace_minutes'          => 'integer',
            'reminder_on_booking'         => 'boolean',
            'reminder_24h'                => 'boolean',
            'reminder_3h'                 => 'boolean',
            'require_confirmation'        => 'boolean',
            'confirmation_deadline_hours' => 'integer',
            'protection_enabled'          => 'boolean',
            'offense_threshold'           => 'integer',
            'offense_window_days'         => 'integer',
            'action_alert_staff'          => 'boolean',
            'action_manual_confirm'       => 'boolean',
            'deposit_enabled'             => 'boolean',
            'deposit_amount'              => 'decimal:2',
        ];
    }

    /**
     * Sensible defaults for most salons (cash-first market).
     * Used when no row exists yet, so the UI always has values to show.
     */
    public static function defaults(): array
    {
        return [
            'cancellation_window_hours'   => 24,
            'late_grace_minutes'          => 15,
            'late_action'                 => 'staff_decides',
            'reminder_channel'            => 'whatsapp',
            'reminder_on_booking'         => true,
            'reminder_24h'                => true,
            'reminder_3h'                 => true,
            'require_confirmation'        => true,
            'confirmation_deadline_hours' => 6,
            'protection_enabled'          => true,
            'offense_threshold'           => 2,
            'offense_window_days'         => 60,
            'action_alert_staff'          => true,
            'action_manual_confirm'       => true,
            'deposit_enabled'             => false,
            'deposit_type'                => 'fixed',
            'deposit_amount'              => 0,
            'deposit_scope'               => 'at_risk',
            'msg_confirm'                 => null,
            'msg_reminder_24h'            => null,
            'msg_reminder_3h'             => null,
            'msg_unconfirmed'             => null,
        ];
    }

    /**
     * Substitute {name} {service} {branch} {date} {time} {link} placeholders.
     * Returns null when the template is blank, so callers fall back to the
     * built-in default message.
     */
    public static function render(?string $raw, array $vars): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $map = [];
        foreach ($vars as $key => $value) {
            $map['{' . $key . '}'] = (string) $value;
        }

        return strtr($raw, $map);
    }

    /** Rendered custom template for a message slot, or null to use the default. */
    public function message(string $key, array $vars): ?string
    {
        return static::render($this->{$key} ?? null, $vars);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
