<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Downscaled WebP copies of images on the public disk, for lists and grids
 * (public branch page, team, services) — uploads are stored at full
 * resolution (often 4–5k px), which is far too heavy to put in a grid.
 *
 *   ImageThumb::url('branches/6/x.webp', 640)  → /storage/thumbs/640/branches/6/x.webp
 *
 * Thumbnails are made once (at upload via warm(), or lazily on first use),
 * cached on disk next to a "thumbs/{width}/" prefix, and regenerated if the
 * source is newer. Never upscales: a source narrower than the requested width
 * is served as-is. Any failure (unknown format, not enough memory to decode
 * safely) falls back to the original URL — a page never breaks over a thumb.
 */
final class ImageThumb
{
    private const DISK    = 'public';
    private const QUALITY = 80;

    /**
     * Resizing a multi-megapixel upload takes ~1s, so a page render generates
     * at most this many missing thumbnails; the rest are served as originals
     * until generated (uploads are warmed; `php artisan images:thumbs` backfills).
     */
    private const PER_REQUEST_BUDGET = 4;

    private static int $generated = 0;

    /** Public URL of a copy at most $width px wide (or the original). */
    public static function url(?string $path, int $width): ?string
    {
        if (! $path) {
            return null;
        }

        $thumb = self::make($path, $width, self::$generated < self::PER_REQUEST_BUDGET);

        return asset('storage/' . ($thumb ?? $path));
    }

    /** Pre-generate thumbnails (call right after an upload). */
    public static function warm(string $path, array $widths): void
    {
        foreach ($widths as $w) {
            self::make($path, (int) $w);
        }
    }

    /** Delete every thumbnail of a source (call when the source is deleted). */
    public static function forget(string $path): void
    {
        $disk = Storage::disk(self::DISK);
        foreach ($disk->directories('thumbs') as $dir) {
            $disk->delete($dir . '/' . self::thumbName($path));
        }
    }

    /** Relative path of the thumbnail, or null when the original should be used. */
    private static function make(string $path, int $width, bool $mayGenerate = true): ?string
    {
        $disk = Storage::disk(self::DISK);
        if ($width <= 0 || ! $disk->exists($path)) {
            return null;
        }

        $source = $disk->path($path);
        $thumb  = 'thumbs/' . $width . '/' . self::thumbName($path);

        if ($disk->exists($thumb) && filemtime($disk->path($thumb)) >= filemtime($source)) {
            return $thumb;
        }

        if (! $mayGenerate) {
            return null;
        }

        $info = @getimagesize($source);
        if (! $info || $info[0] <= $width) {
            return null; // unreadable, or already small enough — serve the original
        }
        if (! self::fitsInMemory($info[0], $info[1])) {
            return null;
        }

        $src = match ($info['mime'] ?? '') {
            'image/jpeg' => @imagecreatefromjpeg($source),
            'image/png'  => @imagecreatefrompng($source),
            'image/webp' => @imagecreatefromwebp($source),
            'image/gif'  => @imagecreatefromgif($source),
            default      => false,
        };
        if (! $src) {
            return null;
        }

        $height = (int) max(1, round($info[1] * $width / $info[0]));
        $dst    = imagecreatetruecolor($width, $height);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $width, $height, $info[0], $info[1]);

        $disk->makeDirectory(dirname($thumb));
        $ok = imagewebp($dst, $disk->path($thumb), self::QUALITY);

        imagedestroy($src);
        imagedestroy($dst);

        self::$generated++;

        return $ok ? $thumb : null;
    }

    /** Thumbnails are always WebP; keep the source's folders so names never collide. */
    private static function thumbName(string $path): string
    {
        return preg_replace('/\.[a-z0-9]+$/i', '', ltrim($path, '/')) . '.webp';
    }

    /**
     * GD needs ~5 bytes per source pixel (plus the destination). Refuse to
     * decode when that would push past the memory limit — a fatal
     * out-of-memory can't be caught.
     */
    private static function fitsInMemory(int $w, int $h): bool
    {
        $limit = self::bytes((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return true; // unlimited
        }

        return memory_get_usage(true) + ($w * $h * 5) + 16 * 1024 * 1024 < $limit;
    }

    private static function bytes(string $v): int
    {
        $v = trim($v);
        if ($v === '' || $v === '-1') {
            return -1;
        }
        $n = (int) $v;

        return match (strtolower(substr($v, -1))) {
            'g'     => $n * 1024 ** 3,
            'm'     => $n * 1024 ** 2,
            'k'     => $n * 1024,
            default => $n,
        };
    }
}
