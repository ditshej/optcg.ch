#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Optcg\Data\DataPaths;
use Optcg\Data\Loader;
use Optcg\Icons\CurlHttpFetcher;
use Optcg\Icons\IconSelector;
use Optcg\Icons\IconWriter;

/**
 * Fetches a local icon for every link that does not have one yet (see
 * docs/plan.md section 5, Icons). Reads data/links/*.yaml, never writes to
 * them: it only adds files under public/link-icons/. Intended to run locally or
 * from the "Fetch icons" GitHub Actions workflow (see README.md), never on
 * the live server.
 */

$rootDir = dirname(__DIR__);
$paths = new DataPaths($rootDir);
$loader = new Loader($paths);
$selector = new IconSelector(new CurlHttpFetcher());
$writer = new IconWriter();

if (!is_dir($paths->iconsDir())) {
    mkdir($paths->iconsDir(), recursive: true);
}

/** @var list<array{room: string, id: string, title: string, status: string, detail: string}> $overview */
$overview = [];
$fetchedCount = 0;

foreach ($loader->discoverLinkFileRoomIds() as $roomId) {
    foreach ($loader->loadLinks($roomId) as $link) {
        $id = (string) ($link['id'] ?? '');
        $title = (string) ($link['title'] ?? $id);
        $url = (string) ($link['url'] ?? '');

        if ($id === '' || $url === '') {
            continue;
        }

        $iconFileName = is_string($link['icon'] ?? null) && $link['icon'] !== '' ? $link['icon'] : "{$id}.png";
        $iconPath = $paths->iconsDir() . '/' . $iconFileName;

        if (($link['icon_source'] ?? null) === 'manual') {
            $overview[] = [$roomId, $id, $title, 'skipped', 'icon_source: manual'];

            continue;
        }

        if (is_file($iconPath)) {
            $overview[] = [$roomId, $id, $title, 'skipped', 'icon file already exists'];

            continue;
        }

        if (!str_ends_with(strtolower($iconFileName), '.png')) {
            $overview[] = [$roomId, $id, $title, 'skipped', "icon field '{$iconFileName}' is not a .png file, fetch-icons.php only writes PNG"];

            continue;
        }

        fwrite(STDERR, "Fetching icon for {$id} ({$url}) ...\n");
        $selected = $selector->selectBestIcon($url);

        if ($selected === null) {
            $overview[] = [$roomId, $id, $title, 'fallback tile', 'no usable icon found (none, or smaller than 64x64)'];

            continue;
        }

        $writer->write($selected, $iconPath);
        $fetchedCount++;
        $overview[] = [
            $roomId,
            $id,
            $title,
            'fetched',
            "{$selected->tier}, {$selected->width}x{$selected->height}px -> public/link-icons/{$iconFileName}",
        ];
    }
}

echo "\n## Icon overview\n\n";
echo "| Room | Link | Result | Detail |\n";
echo "| --- | --- | --- | --- |\n";

foreach ($overview as [$roomId, $id, $title, $status, $detail]) {
    echo "| {$roomId} | {$id} ({$title}) | {$status} | {$detail} |\n";
}

echo "\n{$fetchedCount} icon(s) fetched, " . (count($overview) - $fetchedCount) . " link(s) unchanged.\n";
