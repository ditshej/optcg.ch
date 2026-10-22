<?php

declare(strict_types=1);

namespace Optcg\View;

use LogicException;

/**
 * Where each room sits on the ship drawing and where its callout stands.
 *
 * All numbers live in the drawing space of ShipDrawing (bow on the left).
 * To add a room: draw it in ShipDrawing, then add one entry to ZONES and one to CALLOUTS.
 */
final class ShipLayout
{
    /** Sheet with callouts around the ship (landscape). */
    public const VIEW_PLAN = '-10 -104 1200 694';
    /** The ship alone, cropped to hull, rigging and level scale (portrait). */
    public const VIEW_SHIP = '-6 24 1000 466';

    public const LABEL_W = 180;
    public const LABEL_H = 40;

    /** Room id => hit area [x, y, width, height] on the ship. */
    private const ZONES = [
        'figurehead' => [30, 238, 112, 98],
        'helm' => [176, 222, 160, 108],
        'crows-nest' => [422, 68, 96, 62],
        'library' => [794, 92, 148, 72],
        'lawn-deck' => [346, 262, 288, 68],
        'galley' => [644, 170, 142, 74],
        'sick-bay' => [794, 170, 148, 74],
        'aquarium-bar' => [644, 252, 292, 74],
        'soldier-dock' => [312, 390, 248, 64],
        'usopp-factory' => [588, 374, 268, 80],
    ];

    /** Room id => label box top-left [x, y] and the leader path from the room to the box. */
    private const CALLOUTS = [
        'figurehead' => [[4, -96], 'M86 238V-56'],
        'helm' => [[200, -96], 'M262 222V-56'],
        'crows-nest' => [[396, -96], 'M436 68V-56'],
        'lawn-deck' => [[592, -96], 'M610 262V-56'],
        'galley' => [[788, -96], 'M660 170V-30H860V-56'],
        'library' => [[990, 108], 'M942 128H990'],
        'sick-bay' => [[990, 188], 'M942 208H990'],
        'aquarium-bar' => [[990, 270], 'M936 290H990'],
        'soldier-dock' => [[346, 540], 'M436 454V540'],
        'usopp-factory' => [[680, 540], 'M770 454V540'],
    ];

    /**
     * @return array{zone: string, badge: array{int, int}, box: array{int, int}, boxPoints: string, leader: string, hit: array{int, int}}
     */
    public static function forRoom(string $roomId): array
    {
        if (!isset(self::ZONES[$roomId], self::CALLOUTS[$roomId])) {
            throw new LogicException(sprintf(
                'Room "%s" has no place on the ship yet. Add it to ShipLayout::ZONES and ShipLayout::CALLOUTS (and draw it in ShipDrawing).',
                $roomId,
            ));
        }

        [$x, $y, $w, $h] = self::ZONES[$roomId];
        [$box, $leader] = self::CALLOUTS[$roomId];

        return [
            'zone' => self::chamfer($x, $y, $w, $h, 9),
            'badge' => [$x + 24, $y + 24],
            'box' => $box,
            'boxPoints' => self::chamfer(0, 0, self::LABEL_W, self::LABEL_H, 8),
            'leader' => $leader,
            'hit' => [self::LABEL_W + 12, self::LABEL_H + 12],
        ];
    }

    /** @return list<string> */
    public static function roomIds(): array
    {
        return array_keys(self::ZONES);
    }

    /** Rectangle with cut corners, as a polygon point list. */
    public static function chamfer(float $x, float $y, float $w, float $h, float $c): string
    {
        $pts = [
            [$x + $c, $y], [$x + $w - $c, $y], [$x + $w, $y + $c], [$x + $w, $y + $h - $c],
            [$x + $w - $c, $y + $h], [$x + $c, $y + $h], [$x, $y + $h - $c], [$x, $y + $c],
        ];

        return implode(' ', array_map(static fn (array $p): string => $p[0] . ',' . $p[1], $pts));
    }
}
