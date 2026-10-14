<?php

declare(strict_types=1);

namespace Optcg\Data;

/**
 * One validation problem, always naming the file, the affected link id
 * (when known) and a concrete fix, so a contributor can act on it without
 * reading the validator source.
 */
final class ValidationError
{
    public function __construct(
        public readonly string $file,
        public readonly ?string $linkId,
        public readonly string $message,
        public readonly string $fix,
    ) {
    }

    public function __toString(): string
    {
        $location = $this->linkId !== null
            ? "{$this->file} (link '{$this->linkId}')"
            : $this->file;

        return "{$location}: {$this->message} Fix: {$this->fix}";
    }
}
