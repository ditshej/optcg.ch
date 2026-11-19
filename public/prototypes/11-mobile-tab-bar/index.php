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
$byRoom = [];
$links = [];
$no = [];
foreach ($rooms as $i => $room) {
    $byRoom[$room['id']] = $room;
    $links[$room['id']] = $loader->loadLinks($room['id']);
    $no[$room['id']] = $i + 1;
}
$icons = new IconResolver(static fn (string $f): bool => is_file($paths->iconsDir() . '/' . $f));
$groups = require __DIR__ . '/groups.php';
$groups['all'] = ['label' => 'All', 'rooms' => array_keys($byRoom)];
$total = array_sum(array_map('count', $links));

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Own line pictograms for the tabs, 24 x 24. */
const GLYPHS = [
    'play' => '<rect x="3" y="6" width="12" height="15"/><path d="M9 6V3h12v15h-6"/>',
    'learn' => '<path d="M12 6C9 4 6 4 3 5v14c3-1 6-1 9 1 3-2 6-2 9-1V5c-3-1-6-1-9 1ZM12 6v14"/>',
    'community' => '<circle cx="8" cy="8" r="3.2"/><circle cx="17" cy="9" r="2.6"/><path d="M2 20c0-4 3-6 6-6s6 2 6 6M14.5 14.4c3-.6 7 .6 7 5.6"/>',
    'all' => '<rect x="3" y="3" width="8" height="8"/><rect x="13" y="3" width="8" height="8"/><rect x="3" y="13" width="8" height="8"/><rect x="13" y="13" width="8" height="8"/>',
];

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

/** @param array<string, mixed> $room @param list<array<string, mixed>> $rl */
function roomBlock(array $room, int $no, array $rl, IconResolver $icons, bool $fold): string
{
    $count = count($rl);
    $head = '<span class="r-no">' . sprintf('%02d', $no) . '</span><span class="r-name">' . e($room['name']) . '</span>'
        . '<span class="r-sub">' . e($room['topic']) . ' · ' . e($room['character']) . '</span><span class="r-cnt">' . $count . '</span>';
    $list = $count === 0
        ? '<p class="empty">No links assigned to this compartment yet.</p>'
        : '<ol class="links">' . implode('', array_map(static fn (array $l): string => '<li>' . linkRow($l, $icons) . '</li>', $rl)) . '</ol>';

    return $fold
        ? '<details class="room"><summary>' . $head . '</summary>' . $list . '</details>'
        : '<section class="room" aria-label="' . e($room['name']) . '"><h3 class="r-head">' . $head . '</h3>' . $list . '</section>';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b2a46">
<title>Tab Bar · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="title">
    <h1>Thousand Sunny</h1>
    <p>Sheet 11 · <?= $total ?> links</p>
</header>

<main id="panels">
    <?php $i = 0; foreach ($groups as $gid => $g): $i++; ?>
        <div class="panel" id="panel-<?= e($gid) ?>" role="region" aria-labelledby="tab-<?= e($gid) ?>" data-index="<?= $i - 1 ?>">
            <div class="sheet-head">
                <p class="k">Sheet <?= sprintf('%02d', $i) ?>/<?= count($groups) ?></p>
                <h2><?= $gid === 'all' ? 'All rooms' : e($g['label']) ?></h2>
                <p class="s"><?= count($g['rooms']) ?> rooms · <?= array_sum(array_map(static fn (string $r): int => count($links[$r]), $g['rooms'])) ?> links</p>
            </div>
            <?php foreach ($g['rooms'] as $rid): ?>
                <?= roomBlock($byRoom[$rid], $no[$rid], $links[$rid], $icons, $gid === 'all') ?>
            <?php endforeach; ?>
        </div>
    <?php endforeach; ?>
    <p class="note">Prototype 11 · groups live in groups.php only · local fonts (IBM Plex Mono, Barlow Condensed; OFL) · no external requests</p>
</main>

<nav class="bar" aria-label="Areas" id="bar">
    <span class="ind" id="ind" aria-hidden="true"></span>
    <?php $i = 0; foreach ($groups as $gid => $g): $i++; ?>
        <a id="tab-<?= e($gid) ?>" href="#panel-<?= e($gid) ?>" data-tab="<?= e($gid) ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><?= GLYPHS[$gid] ?></svg>
            <span class="t-name"><?= e($g['label']) ?></span>
            <span class="t-no" aria-hidden="true"><?= sprintf('%02d', $i) ?></span>
        </a>
    <?php endforeach; ?>
</nav>
<script src="tabs.js"></script>
</body>
</html>
