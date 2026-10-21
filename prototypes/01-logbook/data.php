<?php

declare(strict_types=1);

// Loads the real rooms and links through the existing loader (no copy of the data).

require __DIR__ . '/../../vendor/autoload.php';

use Optcg\Data\DataLoadException;
use Optcg\Data\DataPaths;
use Optcg\Data\Loader;

/**
 * @return array{rooms: list<array<string, mixed>>, icon: \Closure(array<string, mixed>): ?string}
 */
function prototype_data(): array
{
    $root = dirname(__DIR__, 2);
    $paths = new DataPaths($root);
    $loader = new Loader($paths);

    $rooms = [];
    foreach ($loader->loadRooms() as $room) {
        try {
            $room['links'] = $loader->loadLinks((string) $room['id']);
        } catch (DataLoadException) {
            $room['links'] = [];
        }
        $rooms[] = $room;
    }

    // Returns the icon path relative to the prototype folder, or null if the file is missing.
    $icon = static function (array $link) use ($paths): ?string {
        $file = is_string($link['icon'] ?? null) && $link['icon'] !== '' ? $link['icon'] : $link['id'] . '.png';

        return is_file($paths->iconsDir() . '/' . $file) ? '../../public/icons/' . rawurlencode($file) : null;
    };

    return ['rooms' => $rooms, 'icon' => $icon];
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
