<?php
namespace local_sectionicons\local;

use invalid_parameter_exception;

/** Validate and normalise uploaded section-icon images. */
final class image_validator {
    public const MAX_BYTES = 1048576;
    public const MAX_DIMENSION = 2048;
    public const MAX_PIXELS = 4194304;

    /** @return array{content:string,mimetype:string,extension:string} */
    public static function sanitise(string $filename, string $content): array {
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($extension === 'svg') {
            return [
                'content' => svg_sanitizer::sanitise($content),
                'mimetype' => 'image/svg+xml',
                'extension' => 'svg',
            ];
        }
        if (!in_array($extension, ['png', 'webp'], true)) {
            throw new invalid_parameter_exception(get_string('imageformat', 'local_sectionicons'));
        }
        if ($content === '' || strlen($content) > self::MAX_BYTES) {
            throw new invalid_parameter_exception(get_string('imagefilesize', 'local_sectionicons'));
        }
        $info = @getimagesizefromstring($content);
        $expectedmime = $extension === 'png' ? 'image/png' : 'image/webp';
        if (!$info || ($info['mime'] ?? '') !== $expectedmime) {
            throw new invalid_parameter_exception(get_string('invalidimage', 'local_sectionicons'));
        }
        $width = (int) ($info[0] ?? 0);
        $height = (int) ($info[1] ?? 0);
        if ($width < 1 || $height < 1 || $width > self::MAX_DIMENSION || $height > self::MAX_DIMENSION
                || $width * $height > self::MAX_PIXELS) {
            throw new invalid_parameter_exception(get_string('imagedimensions', 'local_sectionicons'));
        }
        $hasalpha = $extension === 'png' ? self::png_has_alpha($content) : self::webp_has_alpha($content);
        if (!$hasalpha) {
            throw new invalid_parameter_exception(get_string('imagealpha', 'local_sectionicons'));
        }
        return ['content' => $content, 'mimetype' => $expectedmime, 'extension' => $extension];
    }

    private static function png_has_alpha(string $content): bool {
        if (!str_starts_with($content, "\x89PNG\r\n\x1a\n") || strlen($content) < 33) {
            return false;
        }
        $colourtype = ord($content[25]);
        if (in_array($colourtype, [4, 6], true)) {
            return true;
        }
        $offset = 8;
        $length = strlen($content);
        while ($offset + 12 <= $length) {
            $chunklength = unpack('Nlength', substr($content, $offset, 4))['length'];
            if ($offset + 12 + $chunklength > $length) {
                return false;
            }
            if (substr($content, $offset + 4, 4) === 'tRNS') {
                return true;
            }
            $offset += 12 + $chunklength;
        }
        return false;
    }

    private static function webp_has_alpha(string $content): bool {
        if (strlen($content) < 20 || substr($content, 0, 4) !== 'RIFF' || substr($content, 8, 4) !== 'WEBP') {
            return false;
        }
        $offset = 12;
        $length = strlen($content);
        $hasalpha = false;
        while ($offset + 8 <= $length) {
            $type = substr($content, $offset, 4);
            $chunksize = unpack('Vlength', substr($content, $offset + 4, 4))['length'];
            $dataoffset = $offset + 8;
            if ($dataoffset + $chunksize > $length) {
                return false;
            }
            if ($type === 'ANIM' || $type === 'ANMF'
                    || ($type === 'VP8X' && $chunksize >= 1 && (ord($content[$dataoffset]) & 0x02))) {
                throw new invalid_parameter_exception(get_string('invalidimage', 'local_sectionicons'));
            }
            if ($type === 'ALPH'
                    || ($type === 'VP8X' && $chunksize >= 1 && (ord($content[$dataoffset]) & 0x10))
                    || ($type === 'VP8L' && $chunksize >= 5 && ord($content[$dataoffset]) === 0x2f
                        && (ord($content[$dataoffset + 4]) & 0x10))) {
                $hasalpha = true;
            }
            $offset = $dataoffset + $chunksize + ($chunksize % 2);
        }
        return $hasalpha;
    }
}
