<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * The view model of one link tile's icon: the real icon, the "Add your shop"
 * plus tile, or the fallback tile (first letter on the room's accent
 * colour). Markup is built in templates/_tile-visual.html.twig.
 */
final class TileVisual
{
    /**
     * @param array<string, mixed>|null $link
     * @return array{kind: string, file?: string, initial?: string, color?: string}|null
     */
    public static function view(?array $link, string $color, IconResolver $icons): ?array
    {
        if ($link === null) {
            return null;
        }

        if (!empty($link['is_add_shop'])) {
            return ['kind' => 'add'];
        }

        if ($icons->hasIcon($link)) {
            return ['kind' => 'icon', 'file' => $icons->filename($link)];
        }

        return ['kind' => 'fallback', 'initial' => $icons->initial($link), 'color' => $color];
    }
}
