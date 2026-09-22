<?php

namespace App\Support;

/**
 * Small GD helper that re-encodes an uploaded image to WebP. Shared by the
 * business gallery upload and the GlowRez-team upload so both produce identical
 * output. Falls back to a straight copy when GD cannot decode the source.
 */
class WebpImage
{
    public static function convert(string $sourcePath, string $destPath, int $quality = 82): void
    {
        $mime = @mime_content_type($sourcePath) ?: '';

        $src = match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($sourcePath),
            'image/png'               => self::fromPng($sourcePath),
            'image/gif'               => @imagecreatefromgif($sourcePath),
            'image/webp'              => @imagecreatefromwebp($sourcePath),
            default                   => null,
        };

        if (! $src) {
            @copy($sourcePath, $destPath);
            return;
        }

        imagewebp($src, $destPath, $quality);
        imagedestroy($src);
    }

    /** @return \GdImage|false */
    private static function fromPng(string $path)
    {
        $src = @imagecreatefrompng($path);
        if ($src) {
            imagealphablending($src, false);
            imagesavealpha($src, true);
        }

        return $src;
    }
}
