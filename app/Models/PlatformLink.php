<?php

namespace App\Models;

use App\Support\Platform\PlatformUrl;
use Illuminate\Database\Eloquent\Model;

/**
 * An official GlowRez link (social account or a custom one). Only rows that are
 * active, switched on for the public site and carrying a safe URL ever reach a
 * public page — see PlatformSettings::publicLinks().
 */
class PlatformLink extends Model
{
    protected $fillable = ['kind', 'label', 'url', 'icon', 'is_active', 'show_public', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'show_public' => 'boolean',
            'sort_order'  => 'integer',
        ];
    }

    public function isPreset(): bool
    {
        return $this->kind !== 'custom' && isset(config('platform-settings.link_presets')[$this->kind]);
    }

    /** Icon key understood by PlatformIcons (brand icon for presets, chosen icon for custom links). */
    public function iconKey(): string
    {
        return $this->isPreset() ? $this->kind : ($this->icon ?: 'link');
    }

    /** True when this row may be shown on the public site right now. */
    public function isPubliclyVisible(): bool
    {
        return $this->is_active && $this->show_public && PlatformUrl::isSafe((string) $this->url);
    }
}
