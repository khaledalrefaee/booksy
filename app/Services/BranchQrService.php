<?php

namespace App\Services;

use App\Models\Branch;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Support\Facades\Storage;

/**
 * Generates the branch booking QR code.
 *
 * Design goals (GlowRez identity):
 *   • Maximum scannability  → deep-olive modules on a pure-white field,
 *     High error-correction, and a generous quiet zone. No gradients.
 *   • On-brand              → the real site icon (the same favicon shown in
 *     <head> / Google, public/icons/icon-512.png) sits in the centre inside
 *     a white quiet-zone pad ringed with brand gold.
 *
 * The encoded URL (route('front.branch')) and the storage path never change,
 * so existing links/routes/permissions are untouched — this only re-renders
 * the image.
 */
class BranchQrService
{
    /** Rendered QR size in px (excludes the quiet-zone margin below). */
    private const QR_SIZE = 560;

    /** Quiet zone around the code in px — essential for reliable scanning. */
    private const QR_MARGIN = 36;

    /** Centre emblem width as a fraction of the QR width (kept small so the
     *  High EC level always recovers the covered modules). */
    private const ICON_RATIO = 0.20;

    /**
     * Diagonal brand gradient painted onto the dark modules. ALL three stops
     * are deliberately dark (deep olive → dark bronze-gold → charcoal) so every
     * module keeps a ≥7:1 contrast ratio on white — the colour shifts, but the
     * code stays reliably scannable. Light gold is never used inside the code.
     */
    private const G_FROM = [51, 65, 30];    // deep olive   #33411E
    private const G_MID  = [110, 84, 26];   // dark bronze   #6E541A
    private const G_TO   = [37, 41, 24];    // charcoal-olive #252918

    /** Brand gold used for the thin emblem ring (matches --bk-gold, light). */
    private const GOLD = [199, 161, 90];

    public function generate(Branch $branch): string
    {
        // 1 ── Black-on-white QR, High EC, quiet zone (recoloured next) ──
        $png = (new Builder(
            writer               : new PngWriter(),
            data                 : route('front.branch', $branch),
            encoding             : new Encoding('UTF-8'),
            errorCorrectionLevel : ErrorCorrectionLevel::High,
            size                 : self::QR_SIZE,
            margin               : self::QR_MARGIN,
            roundBlockSizeMode   : RoundBlockSizeMode::Margin,
            foregroundColor      : new Color(0, 0, 0),
            backgroundColor      : new Color(255, 255, 255),
        ))->build()->getString();

        $img = imagecreatefromstring($png);
        imagepalettetotruecolor($img);
        imagealphablending($img, true);
        $w = imagesx($img);
        $h = imagesy($img);

        // 2 ── Paint the all-dark brand gradient onto the modules ──
        $this->applyBrandGradient($img, $w, $h);

        // 3 ── Centre emblem: white quiet-zone pad + site icon + gold ring ──
        $this->stampIcon($img, $w, $h);

        ob_start();
        imagepng($img);
        $data = ob_get_clean();
        imagedestroy($img);

        // Same storage path as before → existing <img> src keeps working.
        if ($branch->qr_code) {
            Storage::disk('public')->delete($branch->qr_code);
        }
        $path = "branches/{$branch->id}/qr.png";
        Storage::disk('public')->put($path, $data);

        return $path;
    }

    /**
     * Recolours the dark QR modules with a diagonal deep-olive → bronze →
     * charcoal gradient. White (background) pixels are left untouched, and a
     * luminance threshold keeps the classification crisp. Because every stop is
     * dark, contrast on white stays ≥7:1 and scannability is preserved.
     */
    private function applyBrandGradient(\GdImage $img, int $w, int $h): void
    {
        [$fr, $fg, $fb] = self::G_FROM;
        [$mr, $mg, $mb] = self::G_MID;
        [$tr, $tg, $tb] = self::G_TO;

        // Cache allocated colours per (rounded) gradient position — huge speedup.
        $cache = [];

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $px  = imagecolorat($img, $x, $y);
                $r8  = ($px >> 16) & 0xFF;
                $g8  = ($px >> 8) & 0xFF;
                $b8  = $px & 0xFF;
                $lum = $r8 * 0.299 + $g8 * 0.587 + $b8 * 0.114;

                if ($lum > 128) {
                    continue; // white / light → background, keep as-is
                }

                // Diagonal 0..1 position, quantised to 64 steps for caching.
                $t   = ($x / $w + $y / $h) / 2.0;
                $key = (int) round($t * 63);
                if (!isset($cache[$key])) {
                    $tt = $key / 63;
                    if ($tt < 0.5) {
                        $k = $tt / 0.5;
                        $r = (int) ($fr + ($mr - $fr) * $k);
                        $g = (int) ($fg + ($mg - $fg) * $k);
                        $b = (int) ($fb + ($mb - $fb) * $k);
                    } else {
                        $k = ($tt - 0.5) / 0.5;
                        $r = (int) ($mr + ($tr - $mr) * $k);
                        $g = (int) ($mg + ($tg - $mg) * $k);
                        $b = (int) ($mb + ($tb - $mb) * $k);
                    }
                    $cache[$key] = imagecolorallocate($img, $r, $g, $b);
                }
                imagesetpixel($img, $x, $y, $cache[$key]);
            }
        }
    }

    /**
     * Draws the brand emblem in the centre of the QR: a white rounded pad
     * (which erases the modules beneath so the icon has its own quiet zone),
     * the real site icon, and a thin gold ring around it.
     */
    private function stampIcon(\GdImage $img, int $w, int $h): void
    {
        $iconBox = (int) round($w * self::ICON_RATIO);   // visible icon size
        $pad     = (int) round($iconBox * 0.16);          // white frame thickness
        $box     = $iconBox + $pad * 2;                    // white pad size
        $x       = (int) (($w - $box) / 2);
        $y       = (int) (($h - $box) / 2);
        $r       = (int) round($box * 0.26);               // corner radius

        [$gr, $gg, $gb] = self::GOLD;
        $white = imagecolorallocate($img, 255, 255, 255);
        $gold  = imagecolorallocate($img, $gr, $gg, $gb);

        // White rounded pad — clears modules behind the emblem (quiet zone).
        $this->filledRounded($img, $x, $y, $x + $box, $y + $box, $r, $white);

        // The real site icon (same favicon as <head> / Google).
        $iconPath = $this->iconPath();
        if ($iconPath) {
            $icon = $this->loadImage($iconPath);
            imagealphablending($icon, true);
            imagecopyresampled(
                $img, $icon,
                $x + $pad, $y + $pad, 0, 0,
                $iconBox, $iconBox, imagesx($icon), imagesy($icon)
            );
            imagedestroy($icon);
        }

        // Thin brand-gold ring framing the emblem.
        $this->roundedBorder($img, $x, $y, $x + $box, $y + $box, $r, $gold, 3);
    }

    /** Resolves the site icon used in <head>; null if none is present. */
    private function iconPath(): ?string
    {
        foreach (['icons/icon-512.png', 'icons/apple-touch-icon.png', 'icons/icon-192.png'] as $rel) {
            $p = public_path($rel);
            if (is_file($p)) {
                return $p;
            }
        }
        return null;
    }

    private function loadImage(string $path): \GdImage
    {
        $mime = @getimagesize($path)['mime'] ?? '';
        return match ($mime) {
            'image/png'  => imagecreatefrompng($path),
            'image/gif'  => imagecreatefromgif($path),
            'image/webp' => imagecreatefromwebp($path),
            default      => imagecreatefromjpeg($path),
        };
    }

    private function filledRounded($img, int $x1, int $y1, int $x2, int $y2, int $r, $color): void
    {
        imagefilledrectangle($img, $x1 + $r, $y1,     $x2 - $r, $y2,     $color);
        imagefilledrectangle($img, $x1,     $y1 + $r, $x2,     $y2 - $r, $color);
        imagefilledellipse($img, $x1 + $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y1 + $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x1 + $r, $y2 - $r, $r * 2, $r * 2, $color);
        imagefilledellipse($img, $x2 - $r, $y2 - $r, $r * 2, $r * 2, $color);
    }

    private function roundedBorder($img, int $x1, int $y1, int $x2, int $y2, int $r, $color, int $t = 1): void
    {
        for ($i = 0; $i < $t; $i++) {
            [$a, $b, $d, $e] = [$x1 + $i, $y1 + $i, $x2 - $i, $y2 - $i];
            imageline($img, $a + $r, $b, $d - $r, $b, $color);
            imageline($img, $a + $r, $e, $d - $r, $e, $color);
            imageline($img, $a, $b + $r, $a, $e - $r, $color);
            imageline($img, $d, $b + $r, $d, $e - $r, $color);
            imagearc($img, $a + $r, $b + $r, $r * 2, $r * 2, 180, 270, $color);
            imagearc($img, $d - $r, $b + $r, $r * 2, $r * 2, 270, 360, $color);
            imagearc($img, $a + $r, $e - $r, $r * 2, $r * 2,  90, 180, $color);
            imagearc($img, $d - $r, $e - $r, $r * 2, $r * 2,   0,  90, $color);
        }
    }
}
