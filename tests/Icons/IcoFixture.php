<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

/**
 * Builds minimal, valid ICO byte strings for ImageInspector tests, with one
 * or more PNG-embedded entries of chosen sizes (classic raw-bitmap ICO
 * entries are not needed here, see ImageInspector's documented limitation).
 */
final class IcoFixture
{
    /**
     * @param list<int> $pngSizes one embedded PNG entry per size
     */
    public static function withPngEntries(array $pngSizes): string
    {
        $entries = '';
        $imagesData = '';
        $offset = 6 + count($pngSizes) * 16;

        foreach ($pngSizes as $size) {
            $png = PngFixture::square($size);
            $byteDim = $size >= 256 ? 0 : $size;

            $entries .= pack('CCCCvvVV', $byteDim, $byteDim, 0, 0, 1, 32, strlen($png), $offset);
            $imagesData .= $png;
            $offset += strlen($png);
        }

        $header = pack('vvv', 0, 1, count($pngSizes));

        return $header . $entries . $imagesData;
    }

    public static function withRawBitmapEntry(int $size): string
    {
        $header = pack('vvv', 0, 1, 1);
        $fakeDibData = str_repeat("\x00", 40);
        $entry = pack('CCCCvvVV', $size, $size, 0, 0, 1, 32, strlen($fakeDibData), 22);

        return $header . $entry . $fakeDibData;
    }
}
