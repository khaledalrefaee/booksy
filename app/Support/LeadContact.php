<?php

namespace App\Support;

/**
 * GlowRez's own public contact points for the pre-launch pages. Both come from
 * .env (LEADS_WHATSAPP_NUMBER / LEADS_INSTAGRAM_URL); when unset they return null
 * and the views simply don't render the button — nothing is ever guessed.
 */
class LeadContact
{
    public static function whatsappUrl(?string $text = null): ?string
    {
        return LeadPhone::whatsappUrl(config('leads.whatsapp_number'), $text);
    }

    public static function instagramUrl(): ?string
    {
        $url = trim((string) config('leads.instagram_url'));

        return $url !== '' && filter_var($url, FILTER_VALIDATE_URL) ? $url : null;
    }
}
