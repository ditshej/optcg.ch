<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * One accent colour per room (plan.md section 9), used for the fallback tile
 * background and small section accents. Values are not part of the data
 * model (data/rooms.yaml); this is presentation only.
 */
final class RoomColors
{
    private const COLORS = [
        'figurehead' => '#8b2a1f',
        'navigation-room' => '#1f5c7a',
        'crows-nest' => '#2f6b3a',
        'library' => '#5b3a73',
        'workshop' => '#9c5518',
        'galley' => '#1f6b63',
        'lawn-deck' => '#8a7418',
        'aquarium-bar' => '#2a3a6b',
    ];

    public static function forRoom(string $roomId): string
    {
        return self::COLORS[$roomId] ?? '#4a3015';
    }
}
