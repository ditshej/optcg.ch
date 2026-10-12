<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

/** Generates small in-memory PNG byte strings for icon fixtures, no files on disk needed. */
final class PngFixture
{
    public static function square(int $size): string
    {
        return self::rectangle($size, $size);
    }

    public static function rectangle(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);

        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
    }
}
