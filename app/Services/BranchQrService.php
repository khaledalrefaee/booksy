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

class BranchQrService
{
    private const W      = 580;
    private const H      = 740;
    private const RADIUS = 24;
    private const BG     = [12,  11,  10];
    private const GOLD   = [201, 162, 39];
    private const GOLD2  = [245, 205, 80];
    private const DARK   = [15,  12,   5];
    private const QR_SIZE   = 460;
    private const QR_MARGIN = 0;

    // Gradient: top-left (near-black) → bottom-right (gold)
    private const G_FROM = [20,  18,  12];
    private const G_TO   = [218, 175,  38];

    private function font(string $v): string
    {
        // Use __DIR__ so the path works on any OS regardless of APP_PATH
        $base = dirname(__DIR__, 2) . '/resources/fonts';

        $candidates = match ($v) {
            'title'   => [
                "{$base}/Inkfree.ttf",
                "{$base}/Gabriola.ttf",
                "{$base}/arialbd.ttf",
                '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            ],
            'booknow' => [
                "{$base}/ERASBD.TTF",
                "{$base}/arialbd.ttf",
                '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
                '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf',
            ],
            default   => ["{$base}/arialbd.ttf"],
        };

        foreach ($candidates as $path) {
            if (file_exists($path)) return $path;
        }

        // Last resort: return first candidate and let GD throw a clear error
        return $candidates[0];
    }

    public function generate(Branch $branch): string
    {
        $company  = $branch->company;
        $logoPath = null;
        if ($company?->logo) {
            $c = Storage::disk('public')->path($company->logo);
            if (file_exists($c)) $logoPath = $c;
        }

        // 1 ── Plain black-on-white QR (no margin so we get max data area) ──
        $rawQr = (new Builder(
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

        $qrRaw = imagecreatefromstring($rawQr);
        // Ensure truecolor so imagecolorat returns RGB not palette index
        imagepalettetotruecolor($qrRaw);
        $qrW = imagesx($qrRaw);
        $qrH = imagesy($qrRaw);

        // 2 ── Apply gradient ─────────────────────────────────────────────────
        $qrFinal = $this->buildGradientQr($qrRaw, $qrW, $qrH);
        imagedestroy($qrRaw);

        // 3 ── Main canvas ────────────────────────────────────────────────────
        [$br,$bgc,$bb]   = self::BG;
        [$gr,$gg,$gb]    = self::GOLD;
        [$g2r,$g2g,$g2b] = self::GOLD2;
        [$dr,$dg,$db]    = self::DARK;

        $img   = imagecreatetruecolor(self::W, self::H);
        $cBg   = imagecolorallocate($img, $br, $bgc, $bb);
        $cGold = imagecolorallocate($img, $gr, $gg, $gb);
        $cG2   = imagecolorallocate($img, $g2r, $g2g, $g2b);
        $cDk   = imagecolorallocate($img, $dr, $dg, $db);

        imagefilledrectangle($img, 0, 0, self::W, self::H, $cBg);
        $this->roundedBorder($img, 1, 1, self::W - 2, self::H - 2, self::RADIUS, $cGold, 2);

        // 4 ── TOP strip ──────────────────────────────────────────────────────
        $topH = 110;
        $sR   = self::RADIUS - 1;
        $this->filledRounded($img, 2, 2, self::W - 3, $topH, $sR, $cGold);

        for ($i = 0; $i < 8; $i++) {
            $dx = (int)(self::W * 0.09 + $i * self::W * 0.12);
            imagefilledellipse($img, $dx, 18, 5, 5, $cG2);
        }

        // ── App name ─────────────────────────────────────────────────────────
        $appName = config('app.name', 'Booksy');
        $cText   = imagecolorallocate($img, 25, 18, 2);
        $cShad   = imagecolorallocate($img, 40, 30, 0);
        $this->drawText($img, $appName, $this->font('title'), 52, self::W, $topH, $cText, $cShad);

        // 5 ── QR directly on dark background (no white card) ────────────────
        $qrX = (int)((self::W - $qrW) / 2);
        $qrY = $topH + 20;
        imagecopy($img, $qrFinal, $qrX, $qrY, 0, 0, $qrW, $qrH);
        imagedestroy($qrFinal);

        // 6 ── BOTTOM strip ───────────────────────────────────────────────────
        $botY = $qrY + $qrH + 20;
        $botH = self::H - $botY - 4;
        $this->filledRounded($img, 2, $botY, self::W - 3, self::H - 3, $sR, $cGold);

        // ── BOOK NOW ─────────────────────────────────────────────────────────
        $cText2 = imagecolorallocate($img, 22, 16, 2);
        $cShad2 = imagecolorallocate($img, 40, 30, 0);
        $this->drawText($img, 'BOOK  NOW', $this->font('booknow'), 28, self::W, $botH, $cText2, $cShad2, $botY);

        // 7 ── Corner accents ─────────────────────────────────────────────────
        $sq = 9; $ins = 10;
        imagefilledrectangle($img, $ins, $ins + 6, $ins + $sq, $ins + $sq + 6, $cG2);
        imagefilledrectangle($img, self::W-$ins-$sq-1, $ins+6, self::W-$ins-1, $ins+$sq+6, $cG2);

        ob_start(); imagepng($img); $data = ob_get_clean();
        imagedestroy($img);

        if ($branch->qr_code) Storage::disk('public')->delete($branch->qr_code);
        $path = "branches/{$branch->id}/qr.png";
        Storage::disk('public')->put($path, $data);
        return $path;
    }

    /**
     * Black→Gold diagonal gradient on dark QR modules.
     * White modules → transparent (dark BG shows through).
     * Logo locked inside white circle at center.
     */
    private function buildGradientQr(\GdImage $qrImg, int $w, int $h): \GdImage
    {
        [$fr, $fg, $fb] = self::G_FROM;
        [$tr, $tg, $tb] = self::G_TO;
        [$br, $bgc, $bb] = self::BG;

        $out = imagecreatetruecolor($w, $h);
        imagealphablending($out, false);
        imagesavealpha($out, true);

        // Fill with dark BG colour (matches card background — no white border visible)
        $cBg = imagecolorallocate($out, $br, $bgc, $bb);
        imagefilledrectangle($out, 0, 0, $w, $h, $cBg);

        for ($y = 0; $y < $h; $y++) {
            $fy = $y / $h;
            for ($x = 0; $x < $w; $x++) {
                $px  = imagecolorat($qrImg, $x, $y);
                $r8  = ($px >> 16) & 0xFF;
                $g8  = ($px >>  8) & 0xFF;
                $b8  =  $px        & 0xFF;
                $lum = (int)($r8 * 0.299 + $g8 * 0.587 + $b8 * 0.114);

                if ($lum > 128) continue; // white module → leave as dark BG

                $t  = ($x / $w + $fy) / 2.0;
                $r  = (int)($fr + ($tr - $fr) * $t);
                $g  = (int)($fg + ($tg - $fg) * $t);
                $b  = (int)($fb + ($tb - $fb) * $t);
                imagesetpixel($out, $x, $y, imagecolorallocate($out, $r, $g, $b));
            }
        }


        return $out;
    }

    /**
     * Draw centered text using TTF if available, GD built-in font otherwise.
     * $stripH = height of the strip, $stripY = top Y of strip (0 for top strip).
     */
    private function drawText(
        \GdImage $img, string $text, string $fontPath, int $sz,
        int $canvasW, int $stripH, $cText, $cShad, int $stripY = 0
    ): void {
        if (file_exists($fontPath)) {
            try {
                $bbox = @imagettfbbox($sz, 0, $fontPath, $text);
                if ($bbox === false) throw new \RuntimeException('bbox failed');
                $tw = abs($bbox[4] - $bbox[0]);
                $th = abs($bbox[5] - $bbox[1]);
                $tx = (int)(($canvasW - $tw) / 2);
                $ty = $stripY + (int)(($stripH + $th) / 2) + 6;
                imagettftext($img, $sz, 0, $tx + 2, $ty + 2, $cShad, $fontPath, $text);
                imagettftext($img, $sz, 0, $tx, $ty, $cText, $fontPath, $text);
                return;
            } catch (\Throwable) {
                // fall through to built-in
            }
        }

        // GD built-in font fallback (no external file needed)
        $gdFont = 5;
        $tw     = imagefontwidth($gdFont) * strlen($text);
        $th     = imagefontheight($gdFont);
        $tx     = (int)(($canvasW - $tw) / 2);
        $ty     = $stripY + (int)(($stripH - $th) / 2);
        imagestring($img, $gdFont, $tx, $ty, $text, $cText);
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
        imagefilledrectangle($img, $x1+$r, $y1,    $x2-$r, $y2,    $color);
        imagefilledrectangle($img, $x1,    $y1+$r, $x2,    $y2-$r, $color);
        imagefilledellipse($img, $x1+$r, $y1+$r, $r*2, $r*2, $color);
        imagefilledellipse($img, $x2-$r, $y1+$r, $r*2, $r*2, $color);
        imagefilledellipse($img, $x1+$r, $y2-$r, $r*2, $r*2, $color);
        imagefilledellipse($img, $x2-$r, $y2-$r, $r*2, $r*2, $color);
    }

    private function roundedBorder($img, int $x1, int $y1, int $x2, int $y2, int $r, $color, int $t = 1): void
    {
        for ($i = 0; $i < $t; $i++) {
            [$a,$b,$d,$e] = [$x1+$i, $y1+$i, $x2-$i, $y2-$i];
            imageline($img, $a+$r, $b, $d-$r, $b, $color);
            imageline($img, $a+$r, $e, $d-$r, $e, $color);
            imageline($img, $a, $b+$r, $a, $e-$r, $color);
            imageline($img, $d, $b+$r, $d, $e-$r, $color);
            imagearc($img, $a+$r, $b+$r, $r*2, $r*2, 180, 270, $color);
            imagearc($img, $d-$r, $b+$r, $r*2, $r*2, 270, 360, $color);
            imagearc($img, $a+$r, $e-$r, $r*2, $r*2,  90, 180, $color);
            imagearc($img, $d-$r, $e-$r, $r*2, $r*2,   0,  90, $color);
        }
    }
}