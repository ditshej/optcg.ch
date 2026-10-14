<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Internal helper for IconSelector: a decoded candidate image together with
 * the URL it came from, used while picking the largest square candidate in
 * one tier.
 */
final class DecodedCandidate
{
    public function __construct(
        public readonly \GdImage $image,
        public readonly int $width,
        public readonly int $height,
        public readonly string $sourceUrl,
    ) {
    }
}
