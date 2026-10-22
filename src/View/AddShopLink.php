<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * The "Add your shop" tile in the Shops (galley) room (plan.md sections 5a
 * and 8): shops are delivered later by the maintainer, so until then this is
 * the only entry. Not part of data/links/galley.yaml; added at render time.
 */
final class AddShopLink
{
    public const ROOM_ID = 'galley';

    /**
     * @return array<string, mixed>
     */
    public static function entry(): array
    {
        return [
            'id' => 'add-your-shop',
            'title' => 'Add your shop',
            'url' => 'https://github.com/ditshej/optcg.ch/issues/new?template=add-your-shop.yml',
            'description' => 'Sell One Piece Card Game products? Suggest your shop for this room.',
            'language' => 'en',
            'tags' => [],
            'featured' => false,
            'is_add_shop' => true,
        ];
    }
}
