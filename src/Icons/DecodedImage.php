<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * A successfully decoded raster image candidate, ready to be scaled down to
 * the stored icon size by bin/fetch-icons.php.
 */
final class DecodedImage
{
    public function __construct(
        public readonly int $width,
        public readonly int $height,
        public readonly \GdImage $image,
    ) {
    }
}
