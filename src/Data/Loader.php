<?php

declare(strict_types=1);

namespace Optcg\Data;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * Reads data/rooms.yaml and data/links/<room-id>.yaml into plain arrays.
 * This class only loads data; see Validator for correctness rules.
 */
final class Loader
{
    public function __construct(
        private readonly DataPaths $paths,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>> rooms as given in the file, sorted by `order`
     */
    public function loadRooms(): array
    {
        $file = $this->paths->roomsFile();
        $data = $this->parseYamlFile($file);

        $rooms = $data['rooms'] ?? [];
        if (!is_array($rooms)) {
            throw new DataLoadException("{$this->paths->relative($file)}: 'rooms' must be a list.");
        }

        usort($rooms, static fn (array $a, array $b): int => ($a['order'] ?? 0) <=> ($b['order'] ?? 0));

        return $rooms;
    }

    /**
     * @return list<string> room ids found in data/rooms.yaml, in file order
     */
    public function loadRoomIds(): array
    {
        return array_values(array_map(
            static fn (array $room): string => (string) ($room['id'] ?? ''),
            $this->loadRooms(),
        ));
    }

    /**
     * @return list<string> room ids derived from data/links/*.yaml file names, sorted alphabetically
     */
    public function discoverLinkFileRoomIds(): array
    {
        $files = glob($this->paths->linksDir() . '/*.yaml') ?: [];
        $ids = array_map(static fn (string $file): string => basename($file, '.yaml'), $files);
        sort($ids);

        return $ids;
    }

    /**
     * @return array<int, array<string, mixed>> links of one room, in file order, with defaults applied
     */
    public function loadLinks(string $roomId): array
    {
        $file = $this->paths->linksFile($roomId);
        $data = $this->parseYamlFile($file);

        $links = $data['links'] ?? [];
        if (!is_array($links)) {
            throw new DataLoadException("{$this->paths->relative($file)}: 'links' must be a list.");
        }

        return array_map(static function (array $link): array {
            $link['tags'] ??= [];
            $link['featured'] ??= false;

            return $link;
        }, $links);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseYamlFile(string $file): array
    {
        if (!is_file($file)) {
            throw new DataLoadException("{$this->paths->relative($file)}: file not found.");
        }

        try {
            $data = Yaml::parseFile($file);
        } catch (ParseException $e) {
            throw new DataLoadException("{$this->paths->relative($file)}: invalid YAML: {$e->getMessage()}", previous: $e);
        }

        return is_array($data) ? $data : [];
    }
}
