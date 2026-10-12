<?php

declare(strict_types=1);

namespace Optcg\LinkCheck;

/**
 * Checked outcome of one link from data/links/<room-id>.yaml.
 */
final class LinkCheckResult
{
    public function __construct(
        public readonly string $roomId,
        public readonly string $linkId,
        public readonly string $title,
        public readonly string $url,
        public readonly bool $ok,
        public readonly string $method,
        public readonly ?int $statusCode,
        public readonly ?string $error,
    ) {
    }

    public function reason(): string
    {
        if ($this->statusCode !== null) {
            return "HTTP {$this->statusCode} (via {$this->method})";
        }

        return ($this->error ?? 'Unknown error') . " (via {$this->method})";
    }
}
