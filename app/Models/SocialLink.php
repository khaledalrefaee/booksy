<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SocialLink extends Model
{
    protected $fillable = [
        'linkable_type',
        'linkable_id',
        'platform',
        'url',
    ];

    /**
     * Platform metadata.
     * base_url  — prefix prepended before the handle when building the full URL.
     * input_type — 'handle' | 'phone' | 'url'
     *   handle → store base_url + handle  (TikTok/YouTube already include '@' in base_url)
     *   phone  → strip non-digits, store base_url + digits
     *   url    → store as-is (website)
     */
    public static array $platforms = [
        'whatsapp'  => ['label' => 'WhatsApp',    'color' => '#25D366', 'input_type' => 'phone',  'base_url' => 'https://wa.me/',             'placeholder' => '963 912 345 678'],
        'instagram' => ['label' => 'Instagram',   'color' => '#E1306C', 'input_type' => 'handle', 'base_url' => 'https://instagram.com/',     'placeholder' => 'username'],
        'facebook'  => ['label' => 'Facebook',    'color' => '#1877F2', 'input_type' => 'handle', 'base_url' => 'https://facebook.com/',      'placeholder' => 'pagename'],
        'twitter'   => ['label' => 'X (Twitter)', 'color' => '#000000', 'input_type' => 'handle', 'base_url' => 'https://x.com/',             'placeholder' => 'username'],
        'tiktok'    => ['label' => 'TikTok',      'color' => '#010101', 'input_type' => 'handle', 'base_url' => 'https://tiktok.com/@',       'placeholder' => 'username'],
        'snapchat'  => ['label' => 'Snapchat',    'color' => '#FFFC00', 'input_type' => 'handle', 'base_url' => 'https://snapchat.com/add/',  'placeholder' => 'username'],
        'youtube'   => ['label' => 'YouTube',     'color' => '#FF0000', 'input_type' => 'handle', 'base_url' => 'https://youtube.com/@',      'placeholder' => 'channel'],
        'linkedin'  => ['label' => 'LinkedIn',    'color' => '#0A66C2', 'input_type' => 'handle', 'base_url' => 'https://linkedin.com/in/',   'placeholder' => 'username'],
        'website'   => ['label' => 'Website',     'color' => '#6366f1', 'input_type' => 'url',    'base_url' => '',                          'placeholder' => 'https://yourwebsite.com'],
    ];

    /**
     * Build the full URL from a raw handle/phone/url value.
     * $dialCode (e.g. "+963") completes a local phone number ("0962…").
     */
    public static function buildUrl(string $platform, string $raw, ?string $dialCode = null): string
    {
        $meta = self::$platforms[$platform] ?? null;
        if (! $meta) return $raw;

        return match ($meta['input_type']) {
            'phone'  => $meta['base_url'] . self::internationalDigits($raw, $dialCode),
            'handle' => $meta['base_url'] . self::cleanHandle($platform, $raw),
            default  => trim($raw),
        };
    }

    /**
     * A handle typed or pasted into a "username" box, made safe to append to
     * the platform's base URL: a pasted profile link is cut down to its
     * username (so it isn't doubled), and whitespace/@ are dropped — usernames
     * never contain spaces, and a space would break the link.
     */
    private static function cleanHandle(string $platform, string $raw): string
    {
        $h = trim($raw);

        if (preg_match('~^(?:https?:)?//|^(?:www\.)?[a-z0-9-]+(?:\.[a-z0-9-]+)*\.[a-z]{2,}/~i', $h)) {
            $parts    = parse_url('https://' . preg_replace('~^(?:https?:)?/*~i', '', $h)) ?: [];
            $path     = $parts['path'] ?? '';
            $basePath = parse_url(self::$platforms[$platform]['base_url'] ?? '', PHP_URL_PATH) ?: '';

            if (strlen($basePath) > 1 && str_starts_with($path, $basePath)) {
                $path = substr($path, strlen($basePath));
            }
            $h = trim($path, '/');

            // Facebook profiles without a username live at profile.php?id=…
            if ($h === 'profile.php' && ! empty($parts['query'])) {
                $h .= '?' . $parts['query'];
            }
        }

        return preg_replace('/\s+/', '', ltrim($h, '@'));
    }

    /**
     * A phone number as international digits only, the form wa.me needs:
     * "+963 962-812 838" / "00963962812838" → "963962812838"; a local
     * "0962812838" gets the dial code (when known) in place of the trunk 0.
     */
    public static function internationalDigits(string $raw, ?string $dialCode = null): string
    {
        $digits = preg_replace('/\D/', '', $raw);

        if (str_starts_with($digits, '00')) {
            return substr($digits, 2);
        }
        $dial = preg_replace('/\D/', '', (string) $dialCode);
        if ($dial !== '' && str_starts_with($digits, '0') && ! str_starts_with(trim($raw), '+')) {
            return $dial . substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Extract the bare handle/number from a stored URL (for pre-filling inputs on edit).
     */
    public static function extractHandle(string $platform, string $url): string
    {
        $meta    = self::$platforms[$platform] ?? null;
        $baseUrl = $meta['base_url'] ?? '';

        if ($baseUrl && str_starts_with($url, $baseUrl)) {
            return substr($url, strlen($baseUrl));
        }
        return $url;
    }

    /**
     * Delete all social links for the given model and re-insert the non-empty ones.
     * $links format: [platform => handle/phone/url]
     */
    public static function syncFor(Model $model, array $links, ?string $dialCode = null): void
    {
        $model->socialLinks()->delete();

        $toInsert = [];
        foreach ($links as $platform => $raw) {
            $raw = trim((string) ($raw ?? ''));
            if ($raw === '' || ! isset(self::$platforms[$platform])) continue;

            $toInsert[] = [
                'platform' => $platform,
                'url'      => self::buildUrl($platform, $raw, $dialCode),
            ];
        }

        if ($toInsert) {
            $model->socialLinks()->createMany($toInsert);
        }
    }

    public function linkable(): MorphTo
    {
        return $this->morphTo();
    }
}
