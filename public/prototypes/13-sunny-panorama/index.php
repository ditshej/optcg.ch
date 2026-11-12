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

/** Side elevation, bow on the left. viewBox 0 20 1200 600. */
const VB = [0, 20, 1200, 600];

/**
 * Where each room lies on the ship (prototype only): room id => [x, y, width, height, station].
 * Stations are the three swipe stops on a phone: bow, mid, stern.
 */
const SLOTS = [
    'figurehead' => [140, 256, 118, 78, 'bow'],
    'helm' => [270, 186, 150, 76, 'bow'],
    'crows-nest' => [505, 44, 170, 76, 'mid'],
    'aquarium-bar' => [430, 342, 330, 70, 'mid'],
    'soldier-dock' => [430, 422, 330, 70, 'mid'],
    'galley' => [805, 190, 160, 70, 'stern'],
    'sick-bay' => [970, 190, 160, 70, 'stern'],
    'library' => [825, 120, 175, 68, 'stern'],
    'navigation-room' => [1005, 120, 125, 68, 'stern'],
    'usopp-factory' => [810, 342, 290, 70, 'stern'],
];
const STATIONS = ['bow' => 'Bow', 'mid' => 'Midship', 'stern' => 'Stern'];

/** Chamfered rectangle path (top right and bottom left cut), like the buttons of prototype 8. */
function chamfer(float $x, float $y, float $w, float $h, float $c = 10): string
{
    return sprintf('M%s %sH%sL%s %sV%sH%sL%s %sZ', $x, $y, $x + $w - $c, $x + $w, $y + $c, $y + $h, $x + $c, $x, $y + $h - $c);
}

/** Paddle wheel seen from the side: rim, hub and paddle blades. */
function wheel(float $cx, float $cy, float $r): string
{
    $s = sprintf('<circle cx="%s" cy="%s" r="%s"/><circle cx="%s" cy="%s" r="%s"/><circle cx="%s" cy="%s" r="7"/>', $cx, $cy, $r, $cx, $cy, $r - 9, $cx, $cy);
    for ($i = 0; $i < 10; $i++) {
        $a = $i * M_PI / 5;
        $c = cos($a);
        $n = sin($a);
        $s .= sprintf('<path d="M%.1f %.1fL%.1f %.1f"/><path d="M%.1f %.1fL%.1f %.1f" class="blade"/>', $cx + 7 * $c, $cy + 7 * $n, $cx + ($r - 9) * $c, $cy + ($r - 9) * $n, $cx + ($r - 9) * $c, $cy + ($r - 9) * $n, $cx + ($r + 5) * $c, $cy + ($r + 5) * $n);
    }

    return $s;
}

/** Lion head of the bow: mane with spikes, face, ears, muzzle. */
function lion(float $cx, float $cy): string
{
    $pts = [];
    for ($i = 0; $i < 28; $i++) {
        $a = $i * 2 * M_PI / 28;
        $r = $i % 2 === 0 ? 40 : 31;
        $pts[] = sprintf('%.1f %.1f', $cx + $r * cos($a), $cy + $r * sin($a));
    }
    $s = '<path class="mane" d="M' . implode('L', $pts) . 'Z"/>';
    $s .= sprintf('<circle class="face" cx="%s" cy="%s" r="21"/>', $cx, $cy + 2);
    $s .= sprintf('<circle cx="%s" cy="%s" r="6"/><circle cx="%s" cy="%s" r="6"/>', $cx - 17, $cy - 17, $cx + 17, $cy - 17);
    $s .= sprintf('<path d="M%s %sc3 -3 7 -3 10 0M%s %sc3 -3 7 -3 10 0"/>', $cx - 15, $cy - 3, $cx + 5, $cy - 3);
    $s .= sprintf('<path d="M%s %sl-5 8h20l-5 -8z"/><path d="M%s %sv8M%s %sc-8 12 -18 12 -22 2M%s %sc8 12 18 12 22 2"/>', $cx, $cy + 6, $cx, $cy + 14, $cx, $cy + 14, $cx, $cy + 14);

    return $s;
}

/** Everything that is drawing only: hull, decks, masts, sail, tree, towers, wheel, dimension lines. */
function drawing(): string
{
    $s = '<g class="draw" aria-hidden="true">';
    // Sea
    $s .= '<path class="water" d="M0 452c20-8 40 8 60 0s40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0"/>';
    $s .= '<text class="dimt" x="14" y="444">WL</text>';
    // Sail and rigging behind the mast
    $s .= '<path class="sail" d="M520 138H660V236C640 246 540 246 520 236Z"/><path class="thin" d="M520 150H660M590 138V242"/>';
    $s .= '<path class="rig" d="M590 126L270 262M590 126L1002 120M590 126L420 262M590 126L805 190"/>';
    // Hull, deck line, tween deck line
    $s .= '<path class="hull" d="M120 330H1130V360C1120 440 1060 500 930 506H430C290 500 170 460 120 330Z"/>';
    $s .= '<path class="thin" d="M150 342H1112M150 416H1112" stroke-dasharray="10 5"/>';
    $s .= '<path class="thin" d="M175 372c60 40 160 80 255 90"/>';
    // Bow cabins (below the helm) with portholes
    $s .= '<path class="struct" d="M262 262H420V330H262Z"/>';
    for ($i = 0; $i < 4; $i++) {
        $s .= sprintf('<circle class="thin" cx="%d" cy="296" r="9"/>', 292 + $i * 40);
    }
    $s .= '<path class="thin" d="M274 270H408"/>';
    // Cabins and stores below deck, bow side (not links, just the ship)
    for ($i = 0; $i < 3; $i++) {
        $s .= sprintf('<circle class="thin" cx="%d" cy="%d" r="8"/>', 250 + $i * 50, 395 - $i * 4);
    }
    // Lawn deck with swing and tree
    $s .= '<path class="lawn" d="M405 318H790V330H405Z"/>';
    for ($x = 412; $x < 790; $x += 12) {
        $s .= sprintf('<path class="tuft" d="M%d 318l2-6 2 6"/>', $x);
    }
    $s .= '<path class="struct" d="M452 330L474 280L496 330M474 280V292"/><path class="thin" d="M468 292V318M480 292V318M465 318H483"/>';
    $s .= '<path class="struct" d="M716 318V268"/><circle class="canopy" cx="716" cy="248" r="30"/><circle class="thin" cx="702" cy="240" r="5"/><circle class="thin" cx="728" cy="256" r="5"/><circle class="thin" cx="722" cy="232" r="4"/>';
    // Main mast, through the lawn deck into the hold
    $s .= '<path class="mast" d="M583 120V500M597 120V500"/>';
    // Stern: three storeys and a roof rail, flag
    $s .= '<path class="struct" d="M800 262H1130V330H800ZM800 190H1130V262H800ZM815 118H1130V190H815Z"/>';
    for ($i = 0; $i < 6; $i++) {
        $s .= sprintf('<path class="thin" d="M%d 282h24v24h-24z"/>', 830 + $i * 52);
    }
    $s .= '<path class="thin" d="M815 112H1130M815 112v6M850 112v6M885 112v6M920 112v6M955 112v6M990 112v6M1025 112v6M1060 112v6M1095 112v6M1130 112v6"/>';
    $s .= '<path class="struct" d="M1112 112V62"/><path class="sail" d="M1112 64h-38l10 12-10 12h38z"/>';
    // Paddle wheel at the stern, behind the hull edge
    $s .= '<g class="wheel">' . wheel(1010, 456, 40) . '</g>';
    // Lion head
    $s .= '<g class="lion">' . lion(78, 300) . '</g>';
    $s .= '';
    // Dimension lines and stations
    $s .= '<path class="dim" d="M120 572H1130" marker-start="url(#arr)" marker-end="url(#arr)"/><text class="dimt" x="625" y="566" text-anchor="middle">LENGTH 1010</text>';
    $s .= '<path class="dim" d="M1160 330V506" marker-start="url(#arr)" marker-end="url(#arr)"/><text class="dimt" transform="translate(1176 418) rotate(90)" text-anchor="middle">DEPTH 176</text>';
    $s .= '<path class="thin" d="M120 560V586M625 560V586M1130 560V586"/>';
    $s .= '<text class="dimt" x="120" y="602" text-anchor="start">BOW</text><text class="dimt" x="625" y="602" text-anchor="middle">MIDSHIP</text><text class="dimt" x="1130" y="602" text-anchor="end">STERN</text>';
    $s .= '<text class="title" x="1180" y="40" text-anchor="end">SHEET 13 · SIDE ELEVATION</text>';

    return $s . '</g>';
}

/** @param list<array<string, mixed>> $rooms @param array<string, list<array<string, mixed>>> $linksByRoom */
function chart(array $rooms, array $linksByRoom): string
{
    [$vx, $vy, $vw, $vh] = VB;
    $s = sprintf('<svg class="chart" id="chart" viewBox="%d %d %d %d" preserveAspectRatio="xMidYMid meet" role="group" aria-label="Side view of the ship with %d rooms" focusable="false">', $vx, $vy, $vw, $vh, count(SLOTS));
    $s .= '<defs><pattern id="grid" width="40" height="40" patternUnits="userSpaceOnUse"><path d="M40 0H0V40" fill="none" stroke="currentColor" stroke-width="1"/></pattern>'
        . '<pattern id="hatch" width="9" height="9" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0V9" stroke="currentColor" stroke-width="1.4"/></pattern>'
        . '<marker id="arr" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0 1L10 5L0 9Z" fill="currentColor"/></marker></defs>';
    $s .= '<rect class="grid" x="-3000" y="-3000" width="7200" height="7200" fill="url(#grid)" aria-hidden="true"/>';
    $s .= drawing();

    $n = 0;
    foreach ($rooms as $room) {
        $n++;
        if (!isset(SLOTS[$room['id']])) {
            continue;
        }
        [$x, $y, $w, $h, $station] = SLOTS[$room['id']];
        $count = count($linksByRoom[$room['id']]);
        $s .= sprintf(
            '<a class="room%1$s" href="#list-%2$s" data-room="%2$s" data-station="%3$s" data-x="%4$d" data-y="%5$d" data-w="%6$d" data-h="%7$d" aria-label="%8$s, %9$d links"><g>'
            . '<path class="fill" d="%10$s"/><path class="hatch" d="%10$s" fill="url(#hatch)"/><path class="edge" d="%10$s"/>'
            . '<text class="no" x="%11$d" y="%12$d">%13$02d</text>'
            . '<text class="ct" x="%14$d" y="%12$d" text-anchor="end">%15$s</text>'
            . '<text class="tp" x="%16$s" y="%17$d" text-anchor="middle">%18$s</text>'
            . '<text class="nm" x="%11$d" y="%19$d">%20$s</text></g></a>',
            $count === 0 ? ' empty' : '',
            e($room['id']),
            $station,
            $x,
            $y,
            $w,
            $h,
            e($room['topic']),
            $count,
            chamfer($x, $y, $w, $h),
            $x + 12,
            $y + 19,
            $n,
            $x + $w - 14,
            $count === 0 ? 'EMPTY' : $count . ($count === 1 ? ' LINK' : ' LINKS'),
            $x + $w / 2,
            $y + 49,
            e(mb_strtoupper($room['topic'])),
            $y + $h - 8,
            e(mb_strtoupper($room['name'])),
        );
    }

    return $s . '</svg>';
}

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

$stationOf = static fn (string $id): string => SLOTS[$id][4] ?? 'mid';
$stationCount = array_fill_keys(array_keys(STATIONS), 0);
foreach ($rooms as $room) {
    $stationCount[$stationOf($room['id'])]++;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="theme-color" content="#0b2a46">
<title>Sunny Panorama · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="first">
    <header class="top">
        <h1>Thousand Sunny</h1>
        <p><?= count(SLOTS) ?> topics · <?= $total ?> links</p>
    </header>

    <main class="stage" id="stage">
        <div class="scroller" id="scroller" tabindex="-1">
            <div class="pan" id="pan"><?= chart($rooms, $linksByRoom) ?>
                <span class="snap s-start" aria-hidden="true"></span><span class="snap s-mid" aria-hidden="true"></span><span class="snap s-end" aria-hidden="true"></span></div>
        </div>
    </main>

    <div class="mini" id="mini" aria-label="Ship overview">
        <svg class="mini-ship" viewBox="0 0 1200 120" preserveAspectRatio="none" aria-hidden="true" focusable="false">
            <path d="M120 70H1130V82C1120 100 1060 112 930 114H430C290 112 170 100 120 70Z"/>
            <path d="M250 50H412V70M583 8V70M800 20H1130V70M800 36H1130"/>
            <path class="lawn" d="M410 66H790"/>
        </svg>
        <i class="view" id="view" aria-hidden="true"></i>
        <?php foreach (STATIONS as $key => $label): ?>
            <button type="button" class="st" data-station="<?= e($key) ?>"><span><?= e($label) ?></span><b><?= $stationCount[$key] ?></b></button>
        <?php endforeach; ?>
    </div>

    <nav class="tiles" id="tiles" aria-label="Topics">
        <?php foreach ($rooms as $i => $room): if (!isset(SLOTS[$room['id']])) { continue; } ?>
            <a class="tile" href="#list-<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>" data-station="<?= e($stationOf($room['id'])) ?>">
                <span class="t-top"><?= e($room['topic']) ?></span>
                <span class="t-cnt"><?= count($linksByRoom[$room['id']]) ?></span>
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
    <p class="note">Prototype 13 · own drawing · local fonts (IBM Plex Mono, Barlow Condensed; OFL) · no external requests</p>
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
<script src="panorama.js"></script>
</body>
</html>
