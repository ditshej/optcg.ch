<?php

declare(strict_types=1);

namespace Optcg\LinkCheck;

/**
 * Outcome of a single HTTP request to a link, as reported by an HttpProbe.
 */
final class ProbeResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?int $statusCode,
        public readonly ?string $error,
    ) {
    }
}
