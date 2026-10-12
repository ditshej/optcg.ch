<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Decodes raw image bytes from an icon candidate (see IconSelector) so its
 * actual size can be checked, regardless of what the HTML or manifest
 * claimed. GD has no ICO reader, so .ico files are only supported when the
 * largest entry is a PNG-in-ICO image, which is what current favicon
 * generators produce; a classic raw-bitmap ICO is treated as undecodable
 * and the next tier (or the fallback tile) is used instead.
 *
 * SVG sources are also treated as undecodable: GD cannot rasterize SVG, and
 * the plan's default `<link-id>.png` icon file name assumes a raster image.
 */
final class ImageInspector
{
    private const ICO_MAGIC = "\x00\x00\x01\x00";
    private const PNG_MAGIC = "\x89PNG\r\n\x1a\n";

    public static function decode(string $bytes): ?DecodedImage
    {
        $fromIco = self::decodeIco($bytes);
        if ($fromIco !== null) {
            return $fromIco;
        }

        $image = @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }

        return new DecodedImage(imagesx($image), imagesy($image), $image);
    }

    private static function decodeIco(string $bytes): ?DecodedImage
    {
        if (!str_starts_with($bytes, self::ICO_MAGIC) || strlen($bytes) < 6) {
            return null;
        }

        $count = unpack('v', substr($bytes, 4, 2))[1] ?? 0;
        $best = null;
        $offset = 6;

        for ($i = 0; $i < $count; $i++) {
            $entry = substr($bytes, $offset, 16);
            $offset += 16;

            if (strlen($entry) < 16) {
                break;
            }

            $width = ord($entry[0]) ?: 256;
            $height = ord($entry[1]) ?: 256;
            $size = unpack('V', substr($entry, 8, 4))[1] ?? 0;
            $dataOffset = unpack('V', substr($entry, 12, 4))[1] ?? 0;

            if ($width === $height && ($best === null || $width > $best['width'])) {
                $best = ['width' => $width, 'offset' => $dataOffset, 'size' => $size];
            }
        }

        if ($best === null) {
            return null;
        }

        $imageData = substr($bytes, $best['offset'], $best['size']);
        if (!str_starts_with($imageData, self::PNG_MAGIC)) {
            return null;
        }

        $image = @imagecreatefromstring($imageData);
        if ($image === false) {
            return null;
        }

        return new DecodedImage(imagesx($image), imagesy($image), $image);
    }
}
