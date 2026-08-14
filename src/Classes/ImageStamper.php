<?php

namespace Nerdcel\ResponsiveImages;

use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\Config;

/**
 * Burns the AI hint text directly into the pixel data of a generated
 * thumbnail and embeds basic IPTC metadata marking the file as AI
 * generated. Unlike the CSS overlay, this survives when the image is
 * downloaded, shared or embedded outside of the page, which is required
 * to satisfy AI content labelling obligations (e.g. EU AI Act Art. 50).
 */
class ImageStamper
{
    /**
     * Bump this whenever the stamping algorithm (font sizing, positioning,
     * etc.) changes, so already-stamped cached files are regenerated
     * instead of keeping stale output (see ResponsiveImages::stampedUrl()).
     */
    public const VERSION = 9;

    private const MIN_FONT_SIZE = 10;

    private const MAX_FONT_SIZE = 120;

    /**
     * Common locations for a regular-weight TTF font shipped with most
     * Linux distributions / macOS, used when no custom font is configured.
     * A regular weight is used (rather than bold) to match the panel
     * preview, which inherits the UI's normal font weight.
     */
    private const FALLBACK_FONTS = [
        '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/dejavu/DejaVuSans.ttf',
        '/usr/share/fonts/truetype/freefont/FreeSans.ttf',
        '/usr/share/fonts/liberation/LiberationSans-Regular.ttf',
        '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        '/System/Library/Fonts/Supplemental/Arial.ttf',
        '/Library/Fonts/Arial.ttf',
        'C:\\Windows\\Fonts\\arial.ttf',
    ];

    /**
     * Stamps the source image with the hint text and writes the result
     * to the destination path. Returns true on success.
     */
    public static function stamp(string $sourcePath, string $destPath, array $aiHint): bool
    {
        if (! function_exists('imagecreatefromstring') || ! is_readable($sourcePath)) {
            return false;
        }

        $data = @file_get_contents($sourcePath);
        if ($data === false) {
            return false;
        }

        $image = @imagecreatefromstring($data);
        if (! $image) {
            return false;
        }

        imagesavealpha($image, true);
        imagealphablending($image, true);

        $width = imagesx($image);
        $height = imagesy($image);
        $text = trim((string) ($aiHint['text'] ?? '')) ?: 'AI generated image';

        $fontSize = self::fontSize($height, (float) ($aiHint['size'] ?? 5));
        $font = self::resolveFont();
        $color = self::hexToRgb($aiHint['color'] ?? '#ffffff');
        $padding = max(4, (int) round($fontSize * (float) ($aiHint['padding'] ?? 0.5)));
        $margin = max(0, (int) round($fontSize * (float) ($aiHint['margin'] ?? 0.6)));
        $backdropColor = ResponsiveImages::contrastBackdropRgb($aiHint['color'] ?? '#ffffff');

        // 0-100 editor-facing opacity, applied to both the text and its
        // backdrop so the whole hint fades consistently, mirroring the
        // CSS overlay's `opacity` style (which multiplies the element's
        // own opacity into the backdrop's fixed 0.45 alpha and the text's
        // fully opaque color).
        $opacity = max(0, min(100, (int) ($aiHint['opacity'] ?? 100))) / 100;
        // GD alpha runs 0 (opaque) - 127 (transparent), inverse of CSS alpha.
        $baseBackdropAlpha = (int) round(127 * (1 - 0.45));
        $backdropAlpha = (int) round(127 - (127 - $baseBackdropAlpha) * $opacity);
        $textAlpha = (int) round(127 * (1 - $opacity));

        [$textWidth, $textHeight] = self::measure($text, $fontSize, $font);
        [$x, $y] = self::position($aiHint['position'] ?? 'bottom-right', $width, $height, $textWidth, $textHeight, $padding, $margin);

        // Semi-transparent backdrop so the text stays legible on any
        // background, with rounded corners (0.25em, matching the
        // panel/CSS preview's border-radius). The backdrop's translucency
        // must be permanently baked into the pixels (not just stored as
        // an alpha channel), since JPEG output has no alpha channel at
        // all and would otherwise render a fully opaque box. A shape mask
        // is used so every real pixel is blended with the photo exactly
        // once, avoiding the double-compositing seams that plain
        // overlapping alpha-blended draw calls would produce at the
        // rounded corners.
        $radius = min((int) round($fontSize * 0.25), (int) floor($textWidth / 2) + $padding, (int) floor($textHeight / 2) + $padding);
        self::drawBackdrop(
            $image,
            (int) round($x - $padding),
            (int) round($y - $padding),
            (int) round($x + $textWidth + $padding),
            (int) round($y + $textHeight + $padding),
            $radius,
            $backdropColor,
            $backdropAlpha
        );

        $textColor = imagecolorallocatealpha($image, $color[0], $color[1], $color[2], $textAlpha);

        if ($font) {
            // imagettftext() draws from the text baseline, not the top-left corner
            imagettftext($image, $fontSize, 0, (int) $x, (int) ($y + $textHeight), $textColor, $font, $text);
        } else {
            imagestring($image, 5, (int) $x, (int) $y, $text, $textColor);
        }

        Dir::make(dirname($destPath), true);

        $format = self::normalizeFormat(pathinfo($destPath, PATHINFO_EXTENSION));
        $saved = self::save($image, $destPath, $format);
        imagedestroy($image);

        if ($saved && in_array($format, ['jpg', 'jpeg'], true)) {
            self::embedIptc($destPath, $text);
        }

        if ($saved) {
            self::embedXmp($destPath, $format, $text, $width, $height);
        }

        return $saved;
    }

    /**
     * Bakes a semi-transparent rounded-rectangle backdrop into the image by
     * manually alpha-blending each pixel with the photo exactly once. This
     * (rather than GD's own alpha-channel/blending) guarantees the
     * translucency survives formats without an alpha channel (JPEG) and
     * avoids the double-compositing seams that overlapping alpha-blended
     * draw calls would otherwise produce at the rounded corners.
     *
     * @param resource|\GdImage $image
     * @param array{0: int, 1: int, 2: int} $color
     */
    private static function drawBackdrop($image, int $x1, int $y1, int $x2, int $y2, int $radius, array $color, int $gdAlpha): void
    {
        $width = imagesx($image);
        $height = imagesy($image);

        $x1 = max(0, $x1);
        $y1 = max(0, $y1);
        $x2 = min($width - 1, $x2);
        $y2 = min($height - 1, $y2);

        $boxWidth = $x2 - $x1 + 1;
        $boxHeight = $y2 - $y1 + 1;

        if ($boxWidth <= 0 || $boxHeight <= 0) {
            return;
        }

        // Build a plain black/white shape mask at the box's own size first.
        // Overlapping draw calls are harmless here since this is a simple
        // 2-colour index image with no blending involved.
        $mask = imagecreate($boxWidth, $boxHeight);
        $outside = imagecolorallocate($mask, 0, 0, 0);
        $inside = imagecolorallocate($mask, 255, 255, 255);
        imagefilledrectangle($mask, 0, 0, $boxWidth - 1, $boxHeight - 1, $outside);
        self::filledRoundedRectangle($mask, 0, 0, $boxWidth - 1, $boxHeight - 1, $radius, $inside);

        // GD alpha runs 0 (opaque) - 127 (transparent); convert to the
        // opaque fraction used for manual linear blending.
        $alphaFraction = 1 - (max(0, min(127, $gdAlpha)) / 127);

        for ($my = 0; $my < $boxHeight; $my++) {
            for ($mx = 0; $mx < $boxWidth; $mx++) {
                if (imagecolorat($mask, $mx, $my) !== $inside) {
                    continue;
                }

                $ix = $x1 + $mx;
                $iy = $y1 + $my;

                $original = imagecolorsforindex($image, imagecolorat($image, $ix, $iy));
                $blended = (
                    (int) round($color[0] * $alphaFraction + $original['red'] * (1 - $alphaFraction)) << 16
                ) | (
                    (int) round($color[1] * $alphaFraction + $original['green'] * (1 - $alphaFraction)) << 8
                ) | (
                    (int) round($color[2] * $alphaFraction + $original['blue'] * (1 - $alphaFraction))
                );

                imagesetpixel($image, $ix, $iy, $blended);
            }
        }

        imagedestroy($mask);
    }

    /**
     * Draws a filled rounded rectangle by combining rectangles and
     * ellipses. Only used to build a plain (non-blended) shape mask, so
     * the pieces overlapping is harmless.
     *
     * @param resource|\GdImage $image
     */
    private static function filledRoundedRectangle($image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        if ($radius <= 0) {
            imagefilledrectangle($image, $x1, $y1, $x2, $y2, $color);

            return;
        }

        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x1 + $radius, $y2 - $radius, $color);
        imagefilledrectangle($image, $x2 - $radius, $y1 + $radius, $x2, $y2 - $radius, $color);

        imagefilledellipse($image, $x1 + $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y1 + $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x1 + $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
        imagefilledellipse($image, $x2 - $radius, $y2 - $radius, $radius * 2, $radius * 2, $color);
    }

    /**
     * Computes a proportional font size (in px) based on the image height
     * and the editor-selected size percentage (a slider, 1-15% of the
     * image height), so the rendered text height stays a fixed percentage
     * of the image height regardless of the source image resolution.
     */
    private static function fontSize(int $height, float $sizePercent): int
    {
        $fontSize = (int) round($height * ($sizePercent / 100));

        return max(self::MIN_FONT_SIZE, min(self::MAX_FONT_SIZE, $fontSize));
    }

    /**
     * Resolves the TTF font file to use, preferring a user-configured
     * path (`aiHint.font`) and falling back to common system fonts.
     * Returns null when no TTF font is available (GD's built-in bitmap
     * font is used instead in that case).
     */
    private static function resolveFont(): ?string
    {
        $configured = Config::get('nerdcel.responsive-images.aiHint.font');

        foreach ([$configured, ...self::FALLBACK_FONTS] as $candidate) {
            if ($candidate && is_readable($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Measures the rendered width/height of the text for the given font size.
     *
     * @return array{0: int, 1: int}
     */
    private static function measure(string $text, int $fontSize, ?string $font): array
    {
        if ($font) {
            $box = imagettfbbox($fontSize, 0, $font, $text);
            $width = abs($box[2] - $box[0]);
            $height = abs($box[7] - $box[1]);

            return [$width, $height];
        }

        return [imagefontwidth(5) * strlen($text), imagefontheight(5)];
    }

    /**
     * Resolves the top-left x/y coordinates for the requested corner /
     * center position, including the padding reserved for the backdrop
     * and the configurable margin (distance to the image edge).
     *
     * @return array{0: int, 1: int}
     */
    private static function position(string $position, int $width, int $height, int $textWidth, int $textHeight, int $padding, int $margin): array
    {
        $offset = $padding + $margin;

        return match ($position) {
            'top-left' => [$offset, $offset],
            'top-right' => [$width - $textWidth - $offset, $offset],
            'center' => [(int) (($width - $textWidth) / 2), (int) (($height - $textHeight) / 2)],
            'bottom-left' => [$offset, $height - $textHeight - $offset],
            default => [$width - $textWidth - $offset, $height - $textHeight - $offset], // bottom-right
        };
    }

    /**
     * @return array{0: int, 1: int, 2: int}
     */
    private static function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');

        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }

        if (strlen($hex) !== 6 || ! ctype_xdigit($hex)) {
            return [255, 255, 255];
        }

        return [
            hexdec(substr($hex, 0, 2)),
            hexdec(substr($hex, 2, 2)),
            hexdec(substr($hex, 4, 2)),
        ];
    }

    private static function normalizeFormat(string $extension): string
    {
        $extension = strtolower($extension);

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    /**
     * @param resource|\GdImage $image
     */
    private static function save($image, string $destPath, string $format): bool
    {
        return match ($format) {
            'jpg' => imagejpeg($image, $destPath, 90),
            'png' => imagepng($image, $destPath),
            'webp' => function_exists('imagewebp') ? imagewebp($image, $destPath, 90) : false,
            'avif' => function_exists('imageavif') ? imageavif($image, $destPath, 80) : false,
            'gif' => imagegif($image, $destPath),
            default => imagepng($image, $destPath),
        };
    }

    /**
     * Embeds basic IPTC IIM metadata (keywords + caption) marking the
     * image as AI generated. This is a best-effort compliance aid; it
     * only applies to JPEG files, since `iptcembed()` only supports the
     * classic IPTC/APP13 segment used by JPEG.
     */
    private static function embedIptc(string $jpegPath, string $text): void
    {
        if (! function_exists('iptcembed')) {
            return;
        }

        $iptc = '';
        $iptc .= self::iptcTag(2, 25, 'AI-generated');
        $iptc .= self::iptcTag(2, 25, 'KI-generiert');
        $iptc .= self::iptcTag(2, 40, 'AI generated image / KI-generiertes Bild');
        $iptc .= self::iptcTag(2, 120, substr($text, 0, 2000));

        $content = iptcembed($iptc, $jpegPath);

        if ($content !== false) {
            F::write($jpegPath, $content);
        }
    }

    private static function iptcTag(int $record, int $tag, string $value): string
    {
        $length = strlen($value);

        return chr(0x1C).chr($record).chr($tag).chr($length >> 8).chr($length & 0xFF).$value;
    }

    /**
     * Embeds an XMP metadata packet marking the image as AI generated,
     * with the hint text as its `dc:description`. Unlike the legacy IPTC
     * IIM block (JPEG only), XMP is supported across JPEG, PNG and WebP,
     * so this covers formats the IPTC embedding above can't reach. Best
     * effort: silently does nothing for formats without an implemented
     * embedding strategy (GIF, AVIF) or if writing fails.
     */
    private static function embedXmp(string $path, string $format, string $text, int $width, int $height): void
    {
        $xmp = self::buildXmpPacket($text);

        try {
            $content = match ($format) {
                'jpg', 'jpeg' => self::embedXmpInJpeg(F::read($path), $xmp),
                'png' => self::embedXmpInPng(F::read($path), $xmp),
                'webp' => self::embedXmpInWebp(F::read($path), $xmp, $width, $height),
                default => null,
            };
        } catch (\Throwable $e) {
            return;
        }

        if ($content !== null && $content !== false) {
            F::write($path, $content);
        }
    }

    /**
     * Builds a minimal XMP packet with the hint text as `dc:description`
     * and the IPTC-standard `DigitalSourceType` marking the image as
     * output of a trained algorithm (i.e. generative AI).
     */
    private static function buildXmpPacket(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<?xpacket begin="'."\xEF\xBB\xBF".'" id="W5M0MpCehiHzreSzNTczkc9d"?>'
            .'<x:xmpmeta xmlns:x="adobe:ns:meta/">'
            .'<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
            .'<rdf:Description rdf:about="" '
            .'xmlns:dc="http://purl.org/dc/elements/1.1/" '
            .'xmlns:Iptc4xmpExt="http://iptc.org/std/Iptc4xmpExt/2008-02-29/">'
            .'<dc:description><rdf:Alt><rdf:li xml:lang="x-default">'.$escaped.'</rdf:li></rdf:Alt></dc:description>'
            .'<Iptc4xmpExt:DigitalSourceType>http://cv.iptc.org/newscodes/digitalsourcetype/trainedAlgorithmicMedia</Iptc4xmpExt:DigitalSourceType>'
            .'</rdf:Description>'
            .'</rdf:RDF>'
            .'</x:xmpmeta>'
            .'<?xpacket end="w"?>';
    }

    /**
     * Inserts the XMP packet as an APP1 segment right after the JPEG's
     * SOI marker (the same convention used by Exif/XMP writers).
     */
    private static function embedXmpInJpeg(string $data, string $xmp): ?string
    {
        if (substr($data, 0, 2) !== "\xFF\xD8") {
            return null;
        }

        $marker = "http://ns.adobe.com/xap/1.0/\0".$xmp;
        $length = strlen($marker) + 2;

        if ($length > 65535) {
            return null;
        }

        $segment = "\xFF\xE1".pack('n', $length).$marker;

        return substr($data, 0, 2).$segment.substr($data, 2);
    }

    /**
     * Inserts the XMP packet as an `iTXt` chunk right before the PNG's
     * `IEND` chunk (the standard location for ancillary metadata chunks
     * added after the fact).
     */
    private static function embedXmpInPng(string $data, string $xmp): ?string
    {
        if (substr($data, 0, 8) !== "\x89PNG\r\n\x1a\n") {
            return null;
        }

        $keyword = 'XML:com.adobe.xmp';
        $chunkData = $keyword."\0\x00\x00\0\0".$xmp;
        $chunk = self::pngChunk('iTXt', $chunkData);

        $pos = 8;
        $len = strlen($data);
        while ($pos + 8 <= $len) {
            $size = unpack('N', substr($data, $pos, 4))[1];
            $type = substr($data, $pos + 4, 4);

            if ($type === 'IEND') {
                return substr($data, 0, $pos).$chunk.substr($data, $pos);
            }

            $pos += 8 + $size + 4;
        }

        return null;
    }

    private static function pngChunk(string $type, string $data): string
    {
        return pack('N', strlen($data)).$type.$data.pack('N', crc32($type.$data));
    }

    /**
     * Inserts the XMP packet as an `XMP ` RIFF chunk in a WebP file,
     * upgrading a simple (non-extended) container to the extended
     * `VP8X` format if needed, or setting the XMP flag and appending the
     * chunk if it's already extended (e.g. has an alpha channel).
     */
    private static function embedXmpInWebp(string $data, string $xmp, int $width, int $height): ?string
    {
        if (substr($data, 0, 4) !== 'RIFF' || substr($data, 8, 4) !== 'WEBP') {
            return null;
        }

        $chunks = [];
        $pos = 12;
        $len = strlen($data);
        while ($pos + 8 <= $len) {
            $fourcc = substr($data, $pos, 4);
            $size = unpack('V', substr($data, $pos + 4, 4))[1];
            $payload = substr($data, $pos + 8, $size);
            $chunks[] = ['fourcc' => $fourcc, 'payload' => $payload];
            $pos += 8 + $size + ($size % 2);
        }

        if (empty($chunks)) {
            return null;
        }

        if ($chunks[0]['fourcc'] === 'VP8X') {
            $flags = ord($chunks[0]['payload'][0]);
            $flags |= 0x04; // XMP flag
            $chunks[0]['payload'][0] = chr($flags);
            // Drop any pre-existing XMP chunk to avoid duplicates
            $chunks = array_values(array_filter(
                $chunks,
                fn($c, $i) => $i === 0 || $c['fourcc'] !== 'XMP ',
                ARRAY_FILTER_USE_BOTH
            ));
        } else {
            $flags = 0x04; // XMP flag only, no alpha/anim/icc/exif
            $vp8xPayload = chr($flags)."\0\0\0"
                .substr(pack('V', max(0, $width - 1)), 0, 3)
                .substr(pack('V', max(0, $height - 1)), 0, 3);
            array_unshift($chunks, ['fourcc' => 'VP8X', 'payload' => $vp8xPayload]);
        }

        $chunks[] = ['fourcc' => 'XMP ', 'payload' => $xmp];

        $body = '';
        foreach ($chunks as $chunk) {
            $payload = $chunk['payload'];
            $body .= $chunk['fourcc'].pack('V', strlen($payload)).$payload;

            if (strlen($payload) % 2 === 1) {
                $body .= "\0";
            }
        }

        return 'RIFF'.pack('V', strlen($body) + 4).'WEBP'.$body;
    }
}
