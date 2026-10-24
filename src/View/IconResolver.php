<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * Decides whether a link shows its real icon (public/link-icons/<file>) or the
 * fallback tile (first letter on a coloured area, plan.md section 5).
 */
final class IconResolver
{
    /**
     * @param \Closure(string): bool $fileExists checks a file name inside public/link-icons/
     */
    public function __construct(
        private readonly \Closure $fileExists,
    ) {
    }

    /**
     * @param array<string, mixed> $link
     */
    public function filename(array $link): string
    {
        $icon = $link['icon'] ?? null;

        return is_string($icon) && $icon !== '' ? $icon : $link['id'] . '.png';
    }

    /**
     * @param array<string, mixed> $link
     */
    public function hasIcon(array $link): bool
    {
        return ($this->fileExists)($this->filename($link));
    }

    /**
     * @param array<string, mixed> $link
     */
    public function initial(array $link): string
    {
        $title = trim((string) ($link['title'] ?? ''));

        return $title === '' ? '?' : mb_strtoupper(mb_substr($title, 0, 1));
    }
}
