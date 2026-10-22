<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';

use Optcg\Data\DataPaths;
use Optcg\Data\Loader;
use Optcg\View\IconResolver;

$paths = new DataPaths($root);
$loader = new Loader($paths);
$rooms = $loader->loadRooms();
$linksByRoom = [];
foreach ($rooms as $room) {
    $linksByRoom[$room['id']] = $loader->loadLinks($room['id']);
}
$icons = new IconResolver(static fn (string $f): bool => is_file($paths->iconsDir() . '/' . $f));
$total = array_sum(array_map('count', $linksByRoom));

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Rooms that have a place in the scene (see scene.js for where). */
const SCENE_ROOMS = ['figurehead', 'helm', 'crows-nest', 'aquarium-bar', 'soldier-dock', 'galley', 'sick-bay', 'library', 'navigation-room', 'usopp-factory'];

/** @param array<string, mixed> $link */
function linkRow(array $link, IconResolver $icons): string
{
    $icon = $icons->hasIcon($link)
        ? '<img src="../../icons/' . e($icons->filename($link)) . '" alt="" width="44" height="44" loading="lazy">'
        : '<span class="fb" aria-hidden="true">' . e($icons->initial($link)) . '</span>';

    return '<a href="' . e($link['url']) . '" target="_blank" rel="noopener noreferrer">' . $icon
        . '<span class="l-txt"><b>' . e($link['title']) . '</b><span>' . e($link['description']) . '</span></span>'
        . '<span class="l-lang">' . e(strtoupper((string) ($link['language'] ?? ''))) . '</span></a>';
}

$scene = [];
foreach ($rooms as $i => $room) {
    if (in_array($room['id'], SCENE_ROOMS, true)) {
        $scene[] = ['id' => $room['id'], 'no' => sprintf('%02d', $i + 1), 'topic' => $room['topic'], 'name' => $room['name'], 'count' => count($linksByRoom[$room['id']])];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b2a46">
<title>Sunny Iso Callouts · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="first">
    <header class="top">
        <h1>Thousand Sunny</h1>
        <p><?= count($scene) ?> topics · <?= $total ?> links</p>
    </header>

    <main class="stage" id="stage">
        <div class="cam" id="cam"><svg class="scene" id="scene" role="img" aria-label="Isometric cutaway of the ship with <?= count($scene) ?> topics" focusable="false"></svg></div>
        <svg class="leaders" id="leaders" aria-hidden="true" focusable="false"></svg>
        <div class="chips" id="chips">
            <?php foreach ($scene as $r): ?>
                <a class="chip<?= $r['count'] === 0 ? ' empty' : '' ?>" href="#list-<?= e($r['id']) ?>" data-room="<?= e($r['id']) ?>" aria-label="<?= e($r['topic']) ?>, <?= $r['count'] ?> links">
                    <span class="c-no"><?= e($r['no']) ?></span>
                    <span class="c-top"><?= e($r['topic']) ?></span>
                    <span class="c-cnt"><?= $r['count'] === 0 ? '0' : $r['count'] ?></span>
                </a>
            <?php endforeach; ?>
        </div>
        <div class="ctl" id="ctl" role="group" aria-label="Turn the ship">
            <button type="button" data-yaw="-1" aria-label="Turn ship left">&larr;</button>
            <button type="button" data-yaw="1" aria-label="Turn ship right">&rarr;</button>
            <button type="button" data-pitch="1" aria-label="Tilt ship up">&uarr;</button>
            <button type="button" data-pitch="-1" aria-label="Tilt ship down">&darr;</button>
            <button type="button" data-reset aria-label="Reset view">Reset</button>
        </div>
        <p class="hint" aria-hidden="true">Drag to turn · tap a topic</p>
    </main>

    <nav class="tiles" id="tiles" aria-label="Topics">
        <?php foreach ($scene as $r): ?>
            <a class="tile" href="#list-<?= e($r['id']) ?>" data-room="<?= e($r['id']) ?>">
                <span class="t-no"><?= e($r['no']) ?></span>
                <span class="t-top"><?= e($r['topic']) ?></span>
                <span class="t-cnt"><?= $r['count'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
</div>

<section class="all" aria-labelledby="all-h">
    <h2 id="all-h">All topics</h2>
    <div class="cols">
    <?php foreach ($rooms as $i => $room): ?>
        <section id="list-<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>" data-no="<?= sprintf('%02d', $i + 1) ?>" data-sub="<?= e($room['name']) ?> · <?= e($room['character']) ?>" data-desc="<?= e($room['description']) ?>">
            <h3>
                <span class="p-no"><?= sprintf('%02d', $i + 1) ?></span>
                <span class="p-top"><?= e($room['topic']) ?></span>
                <span class="p-cnt"><?= count($linksByRoom[$room['id']]) ?></span>
                <span class="p-sub"><?= e($room['name']) ?> · <?= e($room['character']) ?></span>
            </h3>
            <?php if ($linksByRoom[$room['id']] === []): ?>
                <p class="empty">No links assigned to this topic yet.</p>
            <?php else: ?>
                <ol class="links">
                    <?php foreach ($linksByRoom[$room['id']] as $link): ?>
                        <li><?= linkRow($link, $icons) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
    </div>
    <p class="note">Prototype 17 · own drawing, projected in the browser · local fonts (IBM Plex Mono, Barlow Condensed; OFL) · no external requests</p>
</section>

<dialog id="sheet" aria-labelledby="sheet-h">
    <div class="grab" id="grab">
        <span class="bar" aria-hidden="true"></span>
        <header>
            <div>
                <p class="k" id="sheet-k"></p>
                <h2 id="sheet-h" tabindex="-1"></h2>
            </div>
            <button class="close" type="button" id="close" aria-label="Close">Close</button>
        </header>
    </div>
    <div class="body" id="sheet-body"></div>
</dialog>
<script src="ship.js"></script>
<script src="app.js"></script>
</body>
</html>
