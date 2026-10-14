<?php

namespace App\Support;

use App\Services\Platform\PlatformSettings;
use App\Support\Platform\PlatformPhone;

/**
 * GlowRez's own public contact points for the pre-launch pages. They come from the
 * platform settings (Owner → Settings) first and .env (LEADS_WHATSAPP_NUMBER /
 * LEADS_INSTAGRAM_URL) as a fallback; when neither is set they return null
 * and the views simply don't render the button — nothing is ever guessed.
 */
class LeadContact
{
    public static function whatsappUrl(?string $text = null): ?string
    {
        // Owner → Settings → Support (when WhatsApp is visible) takes precedence over .env.
        $ps = app(PlatformSettings::class);
        if ($ps->bool('support.whatsapp_visible') && ($num = PlatformPhone::normalize($ps->string('support.whatsapp')))) {
            return LeadPhone::whatsappUrl($num, $text); // "+<country code>…" so no default country is prepended
        }

        return LeadPhone::whatsappUrl(config('leads.whatsapp_number'), $text);
    }

    public static function instagramUrl(): ?string
    {
        // The Instagram link from Owner → Settings → Links (active + shown on the site), else .env.
        foreach (app(PlatformSettings::class)->publicLinks() as $link) {
            if ($link['kind'] === 'instagram') {
                return $link['url'];
            }
        }

        $url = trim((string) config('leads.instagram_url'));

        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
