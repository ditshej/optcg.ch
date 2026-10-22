<?php

declare(strict_types=1);

namespace Optcg\Tests\View;

use Optcg\Data\DataPaths;
use Optcg\Data\Loader;
use Optcg\View\ShipLayout;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ShipLayout::class)]
final class ShipLayoutTest extends TestCase
{
    public function testEveryRoomOfTheDataHasAPlaceOnTheShipAndViceVersa(): void
    {
        $rooms = (new Loader(new DataPaths(dirname(__DIR__, 2))))->loadRooms();
        $ids = array_column($rooms, 'id');

        self::assertCount(10, $ids);
        self::assertContains('lawn-deck', $ids);
        self::assertNotContains('navigation-room', $ids);

        $placed = ShipLayout::roomIds();
        sort($ids);
        sort($placed);
        self::assertSame($ids, $placed);

        foreach ($ids as $id) {
            self::assertNotSame('', ShipLayout::forRoom($id)['leader']);
        }
    }

    public function testCalloutBoxesStayInsideThePlanView(): void
    {
        [$vx, $vy, $vw, $vh] = array_map('intval', explode(' ', ShipLayout::VIEW_PLAN));

        foreach (ShipLayout::roomIds() as $id) {
            [$x, $y] = ShipLayout::forRoom($id)['box'];
            self::assertGreaterThanOrEqual($vx, $x, $id);
            self::assertGreaterThanOrEqual($vy, $y, $id);
            self::assertLessThanOrEqual($vx + $vw, $x + ShipLayout::LABEL_W, $id);
            self::assertLessThanOrEqual($vy + $vh, $y + ShipLayout::LABEL_H, $id);
        }
    }
}
