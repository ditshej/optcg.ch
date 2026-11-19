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
$byId = [];
foreach ($rooms as $room) {
    $linksByRoom[$room['id']] = $loader->loadLinks($room['id']);
    foreach ($linksByRoom[$room['id']] as $link) {
        $byId[$link['id']] = $link;
    }
}
$icons = new IconResolver(static fn (string $f): bool => is_file($paths->iconsDir() . '/' . $f));
$topLinks = require __DIR__ . '/top-links.php';
$total = array_sum(array_map('count', $linksByRoom));

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Compartments of the upright cross-section (viewBox 390 x 520):
 * room id => [x, y, width, height].
 */
const SLOTS = [
    'crows-nest' => [140, 6, 110, 56],
    'figurehead' => [32, 84, 158, 64],
    'helm' => [200, 84, 158, 64],
    'navigation-room' => [32, 156, 158, 76],
    'library' => [200, 156, 158, 76],
    'galley' => [40, 240, 104, 76],
    'aquarium-bar' => [153, 240, 104, 76],
    'sick-bay' => [266, 240, 94, 76],
    'usopp-factory' => [70, 324, 122, 70],
    'soldier-dock' => [200, 324, 122, 70],
];

/** @param list<string> $lines */
function tspans(array $lines, float $cx, float $cy, int $lh): string
{
    $y0 = $cy - ($lh * (count($lines) - 1)) / 2;
    $out = '';
    foreach ($lines as $i => $line) {
        $out .= sprintf('<tspan x="%s" y="%s">%s</tspan>', $cx, $y0 + $i * $lh, e($line));
    }

    return $out;
}

/** @param list<array<string, mixed>> $rooms @param array<string, list<array<string, mixed>>> $linksByRoom */
function chart(array $rooms, array $linksByRoom): string
{
    $s = '<svg class="chart" viewBox="0 0 390 520" preserveAspectRatio="xMidYMid meet" role="group" aria-label="Cross-section of the ship with ' . count(SLOTS) . ' compartments" focusable="false">';
    $s .= '<defs><pattern id="hatch" width="7" height="7" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0V7" stroke="currentColor" stroke-width="1"/></pattern>'
        . '<marker id="arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="7" markerHeight="7" orient="auto-start-reverse"><path d="M0 1L10 5L0 9Z" fill="currentColor"/></marker></defs>';

    // Sheet frame, grid references and section marks: the margin of a drawing.
    $s .= '<g class="frame" aria-hidden="true">';
    $s .= '<path d="M6 6H384V514H6Z"/>';
    $s .= '<text class="sec" x="14" y="82">A</text><path d="M24 78h10m-4-4l4 4-4 4" class="thin"/>';
    $s .= '<text class="sec" x="376" y="82" text-anchor="end">A</text><path d="M366 78h-10m4-4l-4 4 4 4" class="thin"/>';
    $s .= '</g>';

    // Drawing proper: centre line, mast, rigging, hull, deck, waterline.
    $s .= '<g class="draw" aria-hidden="true">';
    $s .= '<path class="centre" d="M195 62V496"/>';
    $s .= '<path class="mast" d="M189 62V408M201 62V408"/>';
    $s .= '<path class="rig" d="M168 62L30 150M222 62L360 150M195 6L60 150M195 6L330 150"/>';
    $s .= '<path class="hull" d="M20 76H370C372 250 330 400 195 452C60 400 18 250 20 76Z"/>';
    $s .= '<path class="hull" d="M12 76H378"/>';
    $s .= '<path class="keel" d="M178 452H212V464H178Z"/>';
    $s .= '<path class="water" d="M10 358c14-6 28 6 42 0s28-6 42 0 28 6 42 0 28-6 42 0 28 6 42 0 28-6 42 0 28 6 42 0 28-6 42 0 28 6 42 0"/>';
    $s .= '<text class="wl" x="14" y="352">WL</text>';
    $s .= '<path class="dim" d="M373 84V452" marker-start="url(#arr)" marker-end="url(#arr)"/>';
    $s .= '<text class="dimt" transform="translate(382 270) rotate(90)" text-anchor="middle">DEPTH 428</text>';
    $s .= '<path class="dim" d="M20 482H370" marker-start="url(#arr)" marker-end="url(#arr)"/>';
    $s .= '<text class="dimt" x="195" y="500" text-anchor="middle">BEAM 350</text>';
    $s .= '</g>';

    $n = 0;
    foreach ($rooms as $room) {
        $n++;
        if (!isset(SLOTS[$room['id']])) {
            continue;
        }
        [$x, $y, $w, $h] = SLOTS[$room['id']];
        $count = count($linksByRoom[$room['id']]);
        $words = explode(' ', mb_strtoupper($room['name']));
        $lines = $w < 110 && count($words) > 1 ? $words : [implode(' ', $words)];
        $s .= sprintf(
            '<a class="cell%1$s" href="#%2$s" data-room="%2$s" style="--i:%3$d" aria-label="%4$s, %5$d links"><g class="shift">'
            . '<rect class="fill" x="%6$d" y="%7$d" width="%8$d" height="%9$d"/><rect class="hatch" x="%6$d" y="%7$d" width="%8$d" height="%9$d" fill="url(#hatch)"/>'
            . '<rect class="edge" x="%6$d" y="%7$d" width="%8$d" height="%9$d"/>'
            . '<text class="no" x="%10$d" y="%11$d">%12$02d</text>'
            . '<text class="nm" text-anchor="middle">%13$s</text>'
            . '<text class="ct" x="%14$s" y="%15$d" text-anchor="middle">%16$s</text></g></a>',
            $count === 0 ? ' empty' : '',
            e($room['id']),
            $n,
            e($room['name']),
            $count,
            $x,
            $y,
            $w,
            $h,
            $x + 6,
            $y + 14,
            $n,
            tspans($lines, $x + $w / 2, $y + $h / 2 - 1, 18),
            $x + $w / 2,
            $y + $h - 8,
            $count === 0 ? 'EMPTY' : $count . ($count === 1 ? ' LINK' : ' LINKS'),
        );
    }

    return $s . '</svg>';
}

/** @param array<string, mixed> $link */
function linkRow(array $link, IconResolver $icons, string $tag = ''): string
{
    $icon = $icons->hasIcon($link)
        ? '<img src="../../icons/' . e($icons->filename($link)) . '" alt="" width="44" height="44" loading="lazy">'
        : '<span class="fb" aria-hidden="true">' . e($icons->initial($link)) . '</span>';

    return '<a href="' . e($link['url']) . '" target="_blank" rel="noopener noreferrer">' . $icon
        . '<span class="l-txt"><b>' . e($link['title']) . '</b>'
        . ($tag !== '' ? '<i>' . e($tag) . '</i>' : '')
        . '<span>' . e($link['description']) . '</span></span>'
        . '<span class="l-lang">' . e(strtoupper((string) ($link['language'] ?? ''))) . '</span></a>';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b2a46">
<title>Blueprint Hub · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="first">
    <header class="top">
        <div class="brand">
            <h1>Thousand Sunny</h1>
            <p>Sheet 08 · <?= $total ?> links</p>
        </div>
        <nav class="toplinks" aria-label="Top links">
            <?php foreach ($topLinks as $id => $label): ?>
                <?php if (isset($byId[$id])): ?>
                    <a href="<?= e($byId[$id]['url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($label) ?></a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>
        <form class="search" role="search" id="search-form" onsubmit="return false">
            <label for="q">Search links</label>
            <input id="q" type="search" autocomplete="off" enterkeyhint="search" placeholder="Search title, topic, room" spellcheck="false">
        </form>
    </header>

    <main class="stage" id="stage">
        <div class="map" id="map"><?= chart($rooms, $linksByRoom) ?></div>
        <section class="results" id="results" aria-label="Search results">
            <p class="status" id="status" role="status" aria-live="polite"></p>
            <ul id="result-list">
                <?php foreach ($rooms as $room): foreach ($linksByRoom[$room['id']] as $link): ?>
                    <li data-q="<?= e(mb_strtolower($link['title'] . ' ' . $link['description'] . ' ' . $room['name'] . ' ' . $room['topic'] . ' ' . $room['character'])) ?>"><?= linkRow($link, $icons, $room['name']) ?></li>
                <?php endforeach; endforeach; ?>
            </ul>
        </section>
    </main>

    <footer class="legend" aria-hidden="true">
        <span class="scale"><i></i><i></i><i></i><i></i></span>
        <span class="hint">Tap a compartment</span>
        <span class="rev">REV A</span>
    </footer>
</div>

<section class="all" aria-labelledby="all-h">
    <h2 id="all-h">Parts list</h2>
    <?php foreach ($rooms as $i => $room): ?>
        <details id="list-<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>" data-no="<?= sprintf('%02d', $i + 1) ?>" data-topic="<?= e($room['topic']) ?> · <?= e($room['character']) ?>" data-desc="<?= e($room['description']) ?>">
            <summary>
                <span class="p-no"><?= sprintf('%02d', $i + 1) ?></span>
                <span class="p-name"><?= e($room['name']) ?></span>
                <span class="p-sub"><?= e($room['topic']) ?> · <?= e($room['character']) ?></span>
                <span class="p-cnt"><?= count($linksByRoom[$room['id']]) ?></span>
            </summary>
            <?php if ($linksByRoom[$room['id']] === []): ?>
                <p class="empty">No links assigned to this compartment yet.</p>
            <?php else: ?>
                <ol class="links">
                    <?php foreach ($linksByRoom[$room['id']] as $link): ?>
                        <li><?= linkRow($link, $icons) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </details>
    <?php endforeach; ?>
    <p class="note">Prototype 8 · own drawing, abstract ship · local fonts (IBM Plex Mono, Barlow Condensed; OFL) · no external requests</p>
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
<script src="hub.js"></script>
</body>
</html>
