<?php

namespace App\Support;

/**
 * Pure-GD technical inspection of an uploaded image — no AI, no external
 * services. Reports whether a file is a usable image, its dimensions, and two
 * cheap quality heuristics (brightness + Laplacian-variance sharpness) that are
 * only ever used to *hold a photo for review*, never to reject it outright.
 *
 * Rejection is reserved for genuinely unusable files: corrupt, unsupported
 * format, or far below the minimum resolution.
 */
class ImageQuality
{
    public const VERDICT_OK     = 'ok';      // clear → can be auto-approved
    public const VERDICT_REVIEW = 'review';  // possible quality issue → pending
    public const VERDICT_REJECT = 'reject';  // unusable → rejected

    /**
     * @return array{
     *   ok:bool, width:int, height:int, brightness:?float, sharpness:?float,
     *   flags:array<int,string>, verdict:string, reason:?string
     * }
     */
    public static function analyze(string $path): array
    {
        $cfg = config('gallery');

        $result = [
            'ok'         => false,
            'width'      => 0,
            'height'     => 0,
            'brightness' => null,
            'sharpness'  => null,
            'flags'      => [],
            'verdict'    => self::VERDICT_REJECT,
            'reason'     => 'invalid',
        ];

        $info = @getimagesize($path);
        if ($info === false) {
            $result['reason'] = 'corrupt';
            return $result;
        }

        [$width, $height] = $info;
        $mime = $info['mime'] ?? null;
        $result['width']  = (int) $width;
        $result['height'] = (int) $height;

        if (! in_array($mime, $cfg['allowed_mimes'], true)) {
            $result['reason'] = 'unsupported';
            return $result;
        }

        // Only genuinely tiny images (a thumbnail / icon) are rejected outright.
        // A modest-resolution but real photo is accepted and, if under the
        // comfortable minimum, simply held for review below.
        $rejectEdge = (int) ($cfg['reject_min_edge'] ?? 400);
        if (max($width, $height) < $rejectEdge) {
            $result['reason'] = 'low_res';
            return $result;
        }

        $src = self::load($path, $mime);
        if (! $src) {
            $result['reason'] = 'corrupt';
            return $result;
        }

        // Downscale to keep the analysis cheap regardless of source resolution.
        $sample = (int) ($cfg['analysis_sample'] ?? 320);
        $scale  = min(1, $sample / max($width, $height));
        $sw = max(1, (int) round($width * $scale));
        $sh = max(1, (int) round($height * $scale));

        $small = imagecreatetruecolor($sw, $sh);
        imagecopyresampled($small, $src, 0, 0, 0, 0, $sw, $sh, $width, $height);
        imagedestroy($src);

        // Grayscale luminance matrix.
        $gray = [];
        $lumSum = 0;
        for ($y = 0; $y < $sh; $y++) {
            for ($x = 0; $x < $sw; $x++) {
                $rgb = imagecolorat($small, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $lum = (int) round(0.299 * $r + 0.587 * $g + 0.114 * $b);
                $gray[$y][$x] = $lum;
                $lumSum += $lum;
            }
        }
        imagedestroy($small);

        $brightness = $lumSum / ($sw * $sh);
        $sharpness  = self::laplacianVariance($gray, $sw, $sh);

        $flags = [];
        // Below the comfortable minimum but above the hard floor → usable, held
        // for review rather than rejected.
        if ($width < $cfg['min_width'] || $height < $cfg['min_height']) {
            $flags[] = 'low_res';
        }
        if ($brightness < $cfg['dark_threshold']) {
            $flags[] = 'dark';
        }
        if ($sharpness < $cfg['blur_threshold']) {
            $flags[] = 'blurry';
        }

        $result['ok']         = true;
        $result['brightness'] = round($brightness, 1);
        $result['sharpness']  = round($sharpness, 1);
        $result['flags']      = $flags;
        $result['verdict']    = $flags ? self::VERDICT_REVIEW : self::VERDICT_OK;
        $result['reason']     = null;

        return $result;
    }

    /** Variance of the 3x3 Laplacian response — a standard blur proxy. */
    private static function laplacianVariance(array $gray, int $w, int $h): float
    {
        if ($w < 3 || $h < 3) {
            return 0.0;
        }

        $sum = 0.0;
        $count = 0;
        $values = [];
        for ($y = 1; $y < $h - 1; $y++) {
            for ($x = 1; $x < $w - 1; $x++) {
                $lap = $gray[$y - 1][$x] + $gray[$y + 1][$x]
                     + $gray[$y][$x - 1] + $gray[$y][$x + 1]
                     - 4 * $gray[$y][$x];
                $values[] = $lap;
                $sum += $lap;
                $count++;
            }
        }

        if ($count < 2) {
            return 0.0;
        }

        $mean = $sum / $count;
        $var = 0.0;
        foreach ($values as $v) {
            $d = $v - $mean;
            $var += $d * $d;
        }

        return $var / $count;
    }

    /** @return \GdImage|null */
    private static function load(string $path, string $mime)
    {
        $img = match ($mime) {
            'image/jpeg' => @imagecreatefromjpeg($path),
            'image/png'  => @imagecreatefrompng($path),
            'image/webp' => @imagecreatefromwebp($path),
            default      => null,
        };

        return $img ?: null;
    }
}
