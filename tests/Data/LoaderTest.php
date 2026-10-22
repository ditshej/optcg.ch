<?php

declare(strict_types=1);

namespace Optcg\Tests\Data;

use Optcg\Data\DataLoadException;
use Optcg\Data\Loader;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(Loader::class)]
final class LoaderTest extends DataFixtureTestCase
{
    public function testLoadRoomsSortsByOrder(): void
    {
        $this->writeRooms(<<<YAML
            rooms:
              - id: second
                name: Second
                topic: Topic
                description: "Second room."
                order: 2
              - id: first
                name: First
                topic: Topic
                description: "First room."
                order: 1
            YAML);

        $rooms = (new Loader($this->paths()))->loadRooms();

        self::assertSame(['first', 'second'], array_column($rooms, 'id'));
    }

    public function testLoadRoomIds(): void
    {
        $this->writeRooms(<<<YAML
            rooms:
              - id: room-a
                name: Room A
                topic: Topic A
                description: "Room A description."
                order: 1
            YAML);

        self::assertSame(['room-a'], (new Loader($this->paths()))->loadRoomIds());
    }

    public function testLoadLinksAppliesDefaults(): void
    {
        $this->writeLinks('room-a', <<<YAML
            links:
              - id: minimal-link
                title: Minimal Link
                url: "https://example.com"
                description: "A minimal link without tags or featured."
                language: en
                icon_source: manual
            YAML);

        $links = (new Loader($this->paths()))->loadLinks('room-a');

        self::assertSame([], $links[0]['tags']);
        self::assertFalse($links[0]['featured']);
    }

    public function testLoadLinksForMissingFileThrows(): void
    {
        $this->expectException(DataLoadException::class);

        (new Loader($this->paths()))->loadLinks('does-not-exist');
    }

    public function testInvalidYamlThrows(): void
    {
        $this->writeRooms("rooms: [this is not: valid: yaml");

        $this->expectException(DataLoadException::class);

        (new Loader($this->paths()))->loadRooms();
    }

    public function testDiscoverLinkFileRoomIds(): void
    {
        $this->writeLinks('room-b', 'links: []');
        $this->writeLinks('room-a', 'links: []');

        self::assertSame(['room-a', 'room-b'], (new Loader($this->paths()))->discoverLinkFileRoomIds());
    }
}
