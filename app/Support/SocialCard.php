<?php

namespace App\Support;

use RuntimeException;

/**
 * Renders the 1200×630 social share card (Open Graph / X / LinkedIn / WhatsApp)
 * with GD, in the same editorial style as the site.
 *
 * Pure PHP + GD (bundled in Laragon and most hosts); fonts ship in resources/fonts.
 */
class SocialCard
{
    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const BG = [242, 241, 238];

    private const INK = [22, 17, 14];

    private const MUTED = [107, 101, 94];

    private const ACCENT = [228, 87, 31];

    private const LINE = [219, 215, 208];

    /**
     * @param  array{name: string, title: string, tagline?: ?string, location?: ?string,
     *               domain?: ?string, portrait?: ?string, stats?: list<array{0: string, 1: string}>}  $data
     *               portrait: absolute path to a transparent cut-out (png/webp) or a photo (jpg).
     */
    public static function render(array $data, string $outputPath): string
    {
        if (! function_exists('imagettftext')) {
            throw new RuntimeException('The GD extension with FreeType support is required.');
        }

        $W = self::WIDTH;
        $H = self::HEIGHT;
        $im = imagecreatetruecolor($W, $H);
        imagealphablending($im, true);
        imagesavealpha($im, false);

        $c = fn (array $rgb, int $alpha = 0) => imagecolorallocatealpha($im, $rgb[0], $rgb[1], $rgb[2], $alpha);
        imagefilledrectangle($im, 0, 0, $W, $H, $c(self::BG));

        $bold = self::font('InterTight-Bold.ttf');
        $medium = self::font('InterTight-Medium.ttf');
        $regular = self::font('Inter-Regular.ttf');

        // Faint background word, like the hero.
        $ghost = mb_strtoupper(self::lastWord($data['title']) ?: 'DEV');
        imagettftext($im, 230, 0, 520, 300, $c(self::INK, 124), $bold, $ghost);

        // Portrait on the right, bottom-aligned.
        if (! empty($data['portrait']) && is_file($data['portrait'])) {
            self::placePortrait($im, $data['portrait'], x: 680, width: 500);
        }

        $x = 64;

        // Brand row.
        $initials = self::initials($data['name']);
        imagefilledellipse($im, $x + 22, 72, 44, 44, $c(self::INK));
        self::centered($im, 14, $x + 22, 78, $c([255, 255, 255]), $medium, $initials);
        imagettftext($im, 22, 0, $x + 58, 81, $c(self::INK), $medium, mb_strtolower($data['name']));
        $bw = self::width(22, $medium, mb_strtolower($data['name']));
        imagettftext($im, 22, 0, $x + 58 + $bw, 81, $c(self::ACCENT), $medium, '.');

        // Title: "{ Senior }" kicker + big words.
        [$kicker, $big] = self::splitTitle($data['title']);
        $y = 196;
        if ($kicker !== '') {
            imagettftext($im, 30, 0, $x, $y, $c(self::ACCENT), $medium, '{ '.$kicker.' }');
            $y += 18;
        }
        $size = self::fit($big, $bold, 96, 610);
        foreach ($big as $line) {
            $y += (int) round($size * 0.98);
            imagettftext($im, $size, 0, $x - 4, $y, $c(self::INK), $bold, mb_strtoupper($line));
        }

        // Tagline.
        if (! empty($data['tagline'])) {
            $y += 52;
            foreach (self::wrap($data['tagline'], $regular, 22, 560, 2) as $line) {
                imagettftext($im, 22, 0, $x, $y, $c(self::MUTED), $regular, $line);
                $y += 32;
            }
        }

        // Stats row at the bottom.
        $stats = array_slice($data['stats'] ?? [], 0, 3);
        $sy = $H - 84;
        imageline($im, $x, $sy - 62, $x + 580, $sy - 62, $c(self::LINE));
        $sx = $x;
        foreach ($stats as [$value, $label]) {
            imagettftext($im, 34, 0, $sx, $sy - 8, $c(self::INK), $medium, $value);
            $ly = $sy + 20;
            foreach (self::wrap($label, $regular, 13, 170, 2) as $l) {
                imagettftext($im, 13, 0, $sx, $ly, $c(self::MUTED), $regular, $l);
                $ly += 19;
            }
            $sx += 200;
        }

        // Footer chips: domain + location.
        $chip = trim(implode('  ·  ', array_filter([$data['domain'] ?? null, $data['location'] ?? null])));
        if ($chip !== '') {
            $tw = self::width(15, $medium, $chip);
            $cx = $W - 40 - $tw - 36;
            self::roundedRect($im, $cx, $H - 70, $W - 40, $H - 30, 20, $c(self::INK));
            imagettftext($im, 15, 0, $cx + 18, $H - 44, $c([255, 255, 255]), $medium, $chip);
        }

        // Accent corner marks.
        imagefilledrectangle($im, $x - 3, $sy - 65, $x + 3, $sy - 59, $c(self::ACCENT));

        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0755, true);
        }
        imagepng($im, $outputPath, 8);
        imagedestroy($im);

        return $outputPath;
    }

    private static function placePortrait(\GdImage $im, string $path, int $x, int $width): void
    {
        $src = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'webp' => @imagecreatefromwebp($path),
            'png' => @imagecreatefrompng($path),
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            default => false,
        };
        if (! $src) {
            return;
        }
        $sw = imagesx($src);
        $sh = imagesy($src);

        // A normal photo (no transparency) is cropped to fill a framed box instead.
        $isJpeg = in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg'], true);
        if ($isJpeg) {
            [$bx, $by, $bw, $bh] = [$x + 20, 40, $width - 60, self::HEIGHT - 80];
            $scale = max($bw / $sw, $bh / $sh);
            $cw = (int) round($bw / $scale);
            $ch = (int) round($bh / $scale);
            $cx = intdiv($sw - $cw, 2);
            imagecopyresampled($im, $src, $bx, $by, $cx, 0, $bw, $bh, $cw, $ch);
            imagedestroy($src);

            return;
        }

        $height = (int) round($sh * $width / $sw);
        $top = max(40, self::HEIGHT - $height + (int) ($height * 0.12)); // crop a little of the bottom
        imagecopyresampled($im, $src, $x, $top, 0, 0, $width, $height, $sw, $sh);
        imagedestroy($src);
    }

    /** @return array{0: string, 1: list<string>} kicker and 1–2 big lines */
    private static function splitTitle(string $title): array
    {
        $words = preg_split('/\s+/', trim($title)) ?: [];
        if (count($words) <= 1) {
            return ['', $words ?: ['']];
        }
        $two = array_pop($words);
        $one = array_pop($words);

        return [implode(' ', $words), [$one, $two]];
    }

    private static function lastWord(string $s): string
    {
        $w = preg_split('/\s+/', trim($s)) ?: [];

        return (string) end($w);
    }

    private static function initials(string $name): string
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return mb_strtoupper(implode('', array_map(fn ($p) => mb_substr($p, 0, 1), array_slice($parts, 0, 2))));
    }

    private static function fit(array $lines, string $font, int $size, int $maxWidth): int
    {
        foreach ($lines as $line) {
            while ($size > 40 && self::width($size, $font, mb_strtoupper($line)) > $maxWidth) {
                $size -= 2;
            }
        }

        return $size;
    }

    /** @return list<string> */
    private static function wrap(string $text, string $font, int $size, int $maxWidth, int $maxLines): array
    {
        $lines = [];
        $line = '';
        foreach (preg_split('/\s+/', trim($text)) as $word) {
            $try = $line === '' ? $word : "$line $word";
            if (self::width($size, $font, $try) <= $maxWidth) {
                $line = $try;

                continue;
            }
            $lines[] = $line;
            $line = $word;
            if (count($lines) === $maxLines) {
                break;
            }
        }
        if (count($lines) < $maxLines && $line !== '') {
            $lines[] = $line;
            $line = '';
        }
        if ($line !== '' || count($lines) > $maxLines) {
            $last = rtrim(end($lines), ' .,;');
            while ($last !== '' && self::width($size, $font, $last.'…') > $maxWidth) {
                $last = mb_substr($last, 0, -1);
            }
            $lines[count($lines) - 1] = $last.'…';
        }

        return array_slice($lines, 0, $maxLines);
    }

    private static function width(int $size, string $font, string $text): int
    {
        $b = imagettfbbox($size, 0, $font, $text);

        return (int) abs($b[2] - $b[0]);
    }

    private static function centered(\GdImage $im, int $size, int $cx, int $baseline, int $color, string $font, string $text): void
    {
        imagettftext($im, $size, 0, $cx - intdiv(self::width($size, $font, $text), 2), $baseline, $color, $font, $text);
    }

    private static function roundedRect(\GdImage $im, int $x1, int $y1, int $x2, int $y2, int $r, int $color): void
    {
        imagefilledrectangle($im, $x1 + $r, $y1, $x2 - $r, $y2, $color);
        imagefilledrectangle($im, $x1, $y1 + $r, $x2, $y2 - $r, $color);
        foreach ([[$x1 + $r, $y1 + $r], [$x2 - $r, $y1 + $r], [$x1 + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($im, $cx, $cy, $r * 2, $r * 2, $color);
        }
    }

    private static function font(string $file): string
    {
        $path = dirname(__DIR__, 2).'/resources/fonts/'.$file;
        if (! is_file($path)) {
            throw new RuntimeException("Missing font: $path");
        }

        return $path;
    }
}
