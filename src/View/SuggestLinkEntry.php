<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * The "Suggest a link" tile shown as the last tile in every room's overlay
 * except Shops, which keeps only "Add your shop" (plan.md sections 5a and 8).
 * Not part of data/links/<room-id>.yaml; added at render time, after the
 * folder preview is computed, so it never appears in the overview.
 */
final class SuggestLinkEntry
{
    public const ISSUE_TEMPLATE_URL = 'https://github.com/ditshej/optcg.ch/issues/new?template=suggest-a-link.yml';

    /**
     * @param array<string, mixed> $room
     */
    public static function appliesTo(array $room): bool
    {
        return $room['id'] !== AddShopLink::ROOM_ID;
    }

    /**
     * @param array<string, mixed> $room
     * @return array<string, mixed>
     */
    public static function entry(array $room): array
    {
        return [
            'id' => 'suggest-a-link',
            'title' => 'Suggest a link',
            'url' => self::ISSUE_TEMPLATE_URL . '&room=' . rawurlencode($room['name'] . ' (' . $room['topic'] . ')'),
            'description' => '',
            'language' => 'en',
            'tags' => [],
            'featured' => false,
            'is_suggestion' => true,
        ];
    }
}
