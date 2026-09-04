<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffNotification extends Model
{
    protected $fillable = [
        'company_id', 'branch_id', 'type', 'title', 'body',
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

    /**
     * Unified design mapping: notification type → {accent token, Feather SVG}.
     * Drives the premium bell + history page so every notification speaks the
     * same visual language as the GlowRez toast engine (no stored emoji shown).
     */
    private const STYLE = [
        // key => [accent css-var, svg inner markup]
        'booked'    => ['--bk-gold-strong', '<rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/>'],
        'confirmed' => ['--bk-success', '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>'],
        'cancelled' => ['--bk-danger', '<circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/>'],
        'attend'    => ['--bk-warning', '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>'],
        'announce'  => ['--bk-info', '<path d="M3 11l18-5v12L3 14v-3z"/><path d="M11.6 16.8a3 3 0 1 1-5.8-1.6"/>'],
        'default'   => ['--bk-gold-strong', '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>'],
    ];

    private function styleKey(): string
    {
        $t = $this->type ?? '';
        return match (true) {
            str_contains($t, 'booked')                                 => 'booked',
            str_contains($t, 'confirmed')                              => 'confirmed',
            str_contains($t, 'cancelled')                              => 'cancelled',
            str_contains($t, 'announce')                               => 'announce',
            (bool) preg_match('/late|absen|tard|early|attend|leave/', $t) => 'attend',
            default                                                    => 'default',
        };
    }

    /** CSS custom-property name for this notification's accent colour. */
    public function accentToken(): string
    {
        return self::STYLE[$this->styleKey()][0];
    }

    /** Inner markup for a 24×24 stroke SVG (no <svg> wrapper). */
    public function iconSvg(): string
    {
        return self::STYLE[$this->styleKey()][1];
    }

    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function branch(): BelongsTo  { return $this->belongsTo(Branch::class); }
}
