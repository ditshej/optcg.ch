<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * Computes the up-to-4-slot folder preview (plan.md section 9): the first
 * four links, or the featured ones if any are marked, large; a fifth and
 * further link collapse the fourth slot into a small 2x2 grid.
 */
final class FolderPreview
{
    /**
     * @param array<int, array<string, mixed>> $links
     * @return list<array<string, mixed>>
     */
    public static function candidates(array $links): array
    {
        $featured = array_values(array_filter(
            $links,
            static fn (array $link): bool => (bool) ($link['featured'] ?? false),
        ));

        return $featured !== [] ? $featured : array_values($links);
    }

    /**
     * @param array<int, array<string, mixed>> $links
     * @return array{large: list<array<string, mixed>|null>, small: list<array<string, mixed>|null>|null}
     */
    public static function layout(array $links): array
    {
        $candidates = self::candidates($links);

        if (count($candidates) <= 4) {
            return [
                'large' => array_pad(array_slice($candidates, 0, 4), 4, null),
                'small' => null,
            ];
        }

        return [
            'large' => array_slice($candidates, 0, 3),
            'small' => array_pad(array_slice($candidates, 3, 4), 4, null),
        ];
    }
}
