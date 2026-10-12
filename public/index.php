<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Optcg\Data\DataPaths;
use Optcg\Data\Loader;
use Optcg\View\IconResolver;
use Optcg\View\PageRenderer;

$paths = new DataPaths(dirname(__DIR__));
$loader = new Loader($paths);

$rooms = $loader->loadRooms();

$linksByRoomId = [];
foreach ($rooms as $room) {
    $linksByRoomId[$room['id']] = $loader->loadLinks($room['id']);
}

$icons = new IconResolver(
    static fn (string $filename): bool => is_file($paths->iconsDir() . '/' . $filename),
);

echo (new PageRenderer($icons))->render($rooms, $linksByRoomId);
