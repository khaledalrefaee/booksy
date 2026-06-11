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

    /** Supported platforms with metadata */
    public static array $platforms = [
        'whatsapp'  => ['label' => 'WhatsApp',    'color' => '#25D366', 'placeholder' => 'https://wa.me/966XXXXXXXXX'],
        'instagram' => ['label' => 'Instagram',   'color' => '#E1306C', 'placeholder' => 'https://instagram.com/username'],
        'facebook'  => ['label' => 'Facebook',    'color' => '#1877F2', 'placeholder' => 'https://facebook.com/pagename'],
        'twitter'   => ['label' => 'X (Twitter)', 'color' => '#000000', 'placeholder' => 'https://x.com/username'],
        'tiktok'    => ['label' => 'TikTok',      'color' => '#010101', 'placeholder' => 'https://tiktok.com/@username'],
        'snapchat'  => ['label' => 'Snapchat',    'color' => '#FFFC00', 'placeholder' => 'https://snapchat.com/add/username'],
        'youtube'   => ['label' => 'YouTube',     'color' => '#FF0000', 'placeholder' => 'https://youtube.com/@channel'],
        'linkedin'  => ['label' => 'LinkedIn',    'color' => '#0A66C2', 'placeholder' => 'https://linkedin.com/in/username'],
        'website'   => ['label' => 'Website',     'color' => '#6366f1', 'placeholder' => 'https://yourwebsite.com'],
    ];

    /**
     * Delete all social links for the given model and re-insert
     * the non-empty ones from the $links array [platform => url].
     */
    public static function syncFor(Model $model, array $links): void
    {
        $model->socialLinks()->delete();
        $toInsert = [];
        foreach ($links as $platform => $url) {
            $url = trim((string) ($url ?? ''));
            if ($url !== '' && isset(self::$platforms[$platform])) {
                $toInsert[] = ['platform' => $platform, 'url' => $url];
            }
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
