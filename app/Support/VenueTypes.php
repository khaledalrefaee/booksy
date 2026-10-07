<?php

namespace App\Support;

/**
 * Folds the flat category list into the storefront's venue-type groups
 * (config/venue_types.php) so every homepage piece — hero tiles, hero chips,
 * the category directory — reads the same grouping.
 */
class VenueTypes
{
    /** Stable brand-harmonious tones for groups/categories without a photo. */
    private const TONES = ['#4a6a34', '#b4502f', '#7a4585', '#1f7378', '#b07a1c', '#b04062', '#3f52a3', '#7a6244'];

    /**
     * @param  iterable  $categories  categories (with companies_count) that have active places
     * @return array<int, array{key:string,label:string,total:int,names:string,href:string,photo:?string,art:?string,tone:string}>
     */
    public static function tiles(iterable $categories, bool $isAr): array
    {
        $cfg    = config('venue_types');
        $labels = $cfg['groups'] + ['other' => $cfg['other']];

        $by = [];
        foreach ($categories as $cat) {
            $key = 'other';
            foreach ($cfg['groups'] as $k => $g) {
                if (in_array($cat->slug, $g['slugs'], true)) { $key = $k; break; }
            }
            $by[$key][] = $cat;
        }

        $tiles = [];
        foreach ($labels as $key => $g) {
            if (empty($by[$key])) continue;
            $list  = $by[$key];
            $first = $list[0];
            $tiles[] = [
                'key'   => $key,
                'label' => $isAr ? $g['ar'] : $g['en'],
                'total' => (int) collect($list)->sum('companies_count'),
                'names' => collect($list)->map(fn ($c) => $isAr ? $c->name_ar : $c->name_en)->implode(' · '),
                'href'  => route('front.category', $first->slug),
                'photo' => !empty($g['photo']) ? asset($g['photo']) : null,
                'art'   => $first->image ? asset('storage/' . ltrim($first->image, '/')) : null,
                'tone'  => self::TONES[abs(crc32($key)) % count(self::TONES)],
            ];
        }
        return $tiles;
    }


    /**
     * The real category records, ready for display: localized name, active-place count, link, the
     * uploaded cover image and icon (each only when the file really exists — a dangling path would
     * render a broken picture — and an "icon" only when it is roughly square, so an icon *sheet*
     * uploaded by mistake never lands in a small badge), plus a stable fallback tone.
     *
     * @return array<int, array{slug:string,name:string,count:int,href:string,image:?string,icon:?string,tone:string}>
     */
    public static function categories(iterable $categories, bool $isAr): array
    {
        $out = [];
        foreach ($categories as $cat) {
            $out[] = [
                'slug'  => $cat->slug,
                'name'  => $isAr ? ($cat->name_ar ?: $cat->name_en) : ($cat->name_en ?: $cat->name_ar),
                'count' => (int) ($cat->companies_count ?? 0),
                'href'  => route('front.category', $cat->slug),
                'image' => self::publicUrl($cat->image),
                'icon'  => self::publicUrl($cat->icon, true),
                'tone'  => self::TONES[abs(crc32((string) $cat->slug)) % count(self::TONES)],
            ];
        }
        return $out;
    }

    public static function url(?string $path, bool $squareOnly = false): ?string{ return self::publicUrl($path, $squareOnly); }

    private static function publicUrl(?string $path, bool $squareOnly = false): ?string
    {
        if (!$path) return null;
        $file = storage_path('app/public/' . ltrim($path, '/'));
        if (!is_file($file)) return null;
        // an icon is tinted through its alpha channel, so a JPG (no transparency) would paint as a solid disc
        if ($squareOnly && !in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['svg', 'png', 'webp'], true)) return null;
        if ($squareOnly && strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'svg') {
            $dim = @getimagesize($file);
            if (!$dim || $dim[1] === 0) return null;
            $ratio = $dim[0] / $dim[1];
            if ($ratio < 0.8 || $ratio > 1.25) return null;
        }
        return asset('storage/' . ltrim($path, '/'));
    }
}
