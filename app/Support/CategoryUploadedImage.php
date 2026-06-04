<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Resizes category uploads with GD to cut huge PNG/JPEG table payloads.
 * Falls back to the original file if GD cannot handle the type (e.g. SVG).
 */
final class CategoryUploadedImage
{
    public static function storeImage(UploadedFile $file, string $folder = 'categories'): string
    {
        $path = self::tryEncode($file, maxEdge: 1200, asJpeg: true ,folder: $folder);
        if ($path !== null) {
            return $path;
        }

        return $file->store($folder, 'public');
    }

    public static function storeIcon(UploadedFile $file, string $folder = 'categories'): string
    {
        $path = self::tryEncode($file, maxEdge: 512, asJpeg: false , folder: $folder);
        if ($path !== null) {
            return $path;
        }

        return $file->store($folder, 'public');
    }

    private static function tryEncode(UploadedFile $file, int $maxEdge, bool $asJpeg ,string $folder): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $realPath = $file->getRealPath();
        if ($realPath === false) {
            return null;
        }

        $bytes = @file_get_contents($realPath);
        if ($bytes === false || $bytes === '') {
            return null;
        }

        $src = @imagecreatefromstring($bytes);
        if ($src === false) {
            return null;
        }

        $w = imagesx($src);
        $h = imagesy($src);
        if ($w < 1 || $h < 1) {
            imagedestroy($src);

            return null;
        }

        $maxDim = max($w, $h);
        $scale = min(1.0, $maxEdge / $maxDim);
        $smallEnough = $scale >= 1.0 && $file->getSize() < 350 * 1024;
        if ($smallEnough) {
            imagedestroy($src);

            return null;
        }

        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));

        $dst = imagecreatetruecolor($nw, $nh);
        if ($dst === false) {
            imagedestroy($src);

            return null;
        }

        if ($asJpeg) {
            imagealphablending($dst, true);
            $white = imagecolorallocate($dst, 255, 255, 255);
            imagefill($dst, 0, 0, $white);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            ob_start();
            imagejpeg($dst, null, 85);
        } else {
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefill($dst, 0, 0, $transparent);
            imagealphablending($dst, true);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            ob_start();
            imagepng($dst, null, 6);
        }

        $binary = ob_get_clean();
        imagedestroy($src);
        imagedestroy($dst);

        if ($binary === false || $binary === '') {
            return null;
        }

        $ext = $asJpeg ? 'jpg' : 'png';
        $relative = $folder.'/'.Str::uuid()->toString().'.'.$ext;
        Storage::disk('public')->put($relative, $binary);

        return $relative;
    }
}
