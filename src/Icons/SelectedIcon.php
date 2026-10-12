<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * The winning icon candidate for one link, as chosen by IconSelector.
 * `tier` is the source that provided it (see docs/plan.md section 5), kept
 * for the "which link has which icon" overview in the pull request.
 */
final class SelectedIcon
{
    public function __construct(
        public readonly \GdImage $image,
        public readonly int $width,
        public readonly int $height,
        public readonly string $sourceUrl,
        public readonly string $tier,
    ) {
    }
}
