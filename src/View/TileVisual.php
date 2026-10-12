<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * The icon of one link tile: the real icon, the "Add your shop" plus tile,
 * or the fallback tile (first letter on the room's accent colour).
 */
final class TileVisual
{
    /**
     * @param array<string, mixed>|null $link
     */
    public static function render(?array $link, string $color, IconResolver $icons): string
    {
        if ($link === null) {
            return '';
        }

        if (!empty($link['is_add_shop'])) {
            return '<span class="tile-add" aria-hidden="true">+</span>';
        }

        if ($icons->hasIcon($link)) {
            $file = Html::e($icons->filename($link));

            return '<img src="icons/' . $file . '" alt="" loading="lazy" width="64" height="64">';
        }

        $initial = Html::e($icons->initial($link));
        $bg = Html::e($color);

        return '<span class="tile-fallback" style="background-color: ' . $bg . '" aria-hidden="true">' . $initial . '</span>';
    }
}
