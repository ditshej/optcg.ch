<?php

declare(strict_types=1);

$root = dirname(__DIR__, 3);
require $root . '/vendor/autoload.php';
require __DIR__ . '/ship.php';

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
$numbers = [];
foreach ($rooms as $i => $room) {
    $numbers[$room['id']] = sprintf('%02d', $i + 1);
}

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function countLabel(int $n): string
{
    return $n === 0 ? 'no links yet' : $n . ($n === 1 ? ' link' : ' links');
}

/** One room as an SVG hit area with its number badge. */
function zone(array $room, array $links, string $no, string $badgeR): string
{
    [$x, $y, $w, $h] = ZONES[$room['id']]['box'];
    [$bx, $by] = ZONES[$room['id']]['badge'];
    $pts = chamfer($x, $y, $w, $h, 9);

    return sprintf(
        '<a class="zone%1$s" href="#%2$s" data-room="%2$s" tabindex="-1" aria-hidden="true">'
        . '<polygon class="fill" points="%3$s"/><polygon class="hatch" points="%3$s" fill="url(#hatch)"/><polygon class="edge" points="%3$s"/>'
        . '<g class="badge"><circle cx="%4$d" cy="%5$d" r="%6$s"/><text x="%4$d" y="%5$d">%7$s</text></g></a>',
        $links === [] ? ' zero' : '',
        e($room['id']),
        $pts,
        $bx,
        $by,
        $badgeR,
        $no,
    );
}

/** One callout: leader line plus label box with number, topic and link count. */
function callout(array $room, array $links, string $no): string
{
    ['at' => [$x, $y], 'leader' => $leader] = CALLOUTS[$room['id']];

    return sprintf(
        '<a class="callout%1$s" href="#%2$s" data-room="%2$s" aria-label="%3$s, %4$s">'
        . '<path class="leader" d="%5$s"/><g transform="translate(%6$d %7$d)">'
        . '<rect class="hit" x="-6" y="-6" width="%8$d" height="%9$d"/>'
        . '<polygon class="box" points="%10$s"/>'
        . '<text class="cn" x="12" y="19">%11$s · %12$s</text>'
        . '<text class="ct" x="12" y="43">%13$s</text></g></a>',
        $links === [] ? ' zero' : '',
        e($room['id']),
        e($room['topic']),
        countLabel(count($links)),
        $leader,
        $x,
        $y,
        LABEL_W + 12,
        LABEL_H + 12,
        chamfer(0, 0, LABEL_W, LABEL_H, 8),
        $no,
        e(strtoupper(countLabel(count($links)))),
        e(mb_strtoupper($room['topic'])),
    );
}

/** @param list<array<string, mixed>> $rooms */
function planSheet(array $rooms, array $linksByRoom, array $numbers): string
{
    $s = '<svg class="chart plan" viewBox="' . VIEW_PLAN . '" preserveAspectRatio="xMidYMid meet" role="group" aria-label="Side elevation of the ship with ten labelled rooms" focusable="false">';
    $s .= ship_drawing();
    $s .= '<g class="titleblock" aria-hidden="true"><path d="M900 528H1146V594H900ZM900 552H1146"/>'
        . '<text x="910" y="545" class="tb1">THOUSAND SUNNY</text><text x="910" y="574">SIDE ELEVATION · SHEET 12</text><text x="910" y="587">REV A · BOW LEFT</text></g>';
    foreach ($rooms as $room) {
        $s .= zone($room, $linksByRoom[$room['id']], $numbers[$room['id']], '15');
    }
    foreach ($rooms as $room) {
        $s .= callout($room, $linksByRoom[$room['id']], $numbers[$room['id']]);
    }

    return $s . '</svg>';
}

/** @param list<array<string, mixed>> $rooms */
function shipSheet(array $rooms, array $linksByRoom, array $numbers): string
{
    $s = '<svg class="chart ship" viewBox="' . VIEW_SHIP . '" preserveAspectRatio="xMidYMid meet" role="group" aria-label="Side elevation of the ship with ten numbered rooms" focusable="false">';
    $s .= ship_drawing();
    foreach ($rooms as $room) {
        $s .= zone($room, $linksByRoom[$room['id']], $numbers[$room['id']], '19');
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
        . '<span class="l-txt"><b>' . e($link['title']) . '</b>'
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
<title>Sunny Callouts · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?= ship_defs() ?>
<div class="first">
    <header class="top">
        <h1>Thousand Sunny</h1>
        <p>Sheet 12 · <?= $total ?> links</p>
    </header>

    <main class="stage" id="stage">
        <div class="sheetwrap" id="sheetwrap">
            <?= planSheet($rooms, $linksByRoom, $numbers) ?>
            <?= shipSheet($rooms, $linksByRoom, $numbers) ?>
        </div>
    </main>

    <nav class="themes" aria-label="Topics">
        <ol>
            <?php foreach ($rooms as $room): $n = count($linksByRoom[$room['id']]); ?>
                <li><a href="#<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>"<?= $n === 0 ? ' class="zero"' : '' ?>>
                    <span class="t-no"><?= $numbers[$room['id']] ?></span>
                    <span class="t-txt"><b><?= e($room['topic']) ?></b><i><?= e(countLabel($n)) ?></i></span>
                </a></li>
            <?php endforeach; ?>
        </ol>
    </nav>
</div>

<section class="all" aria-labelledby="all-h">
    <h2 id="all-h">All topics</h2>
    <?php foreach ($rooms as $room): $links = $linksByRoom[$room['id']]; ?>
        <details id="list-<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>" data-no="<?= $numbers[$room['id']] ?>" data-topic="<?= e($room['topic']) ?>" data-sub="<?= e($room['name']) ?> · <?= e($room['character']) ?>" data-desc="<?= e($room['description']) ?>">
            <summary>
                <span class="p-no"><?= $numbers[$room['id']] ?></span>
                <span class="p-name"><?= e($room['topic']) ?></span>
                <span class="p-sub"><?= e($room['name']) ?> · <?= e($room['character']) ?></span>
                <span class="p-cnt"><?= count($links) ?></span>
            </summary>
            <?php if ($links === []): ?>
                <p class="empty">No links assigned to this topic yet.</p>
            <?php else: ?>
                <ol class="links">
                    <?php foreach ($links as $link): ?>
                        <li><?= linkRow($link, $icons) ?></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </details>
    <?php endforeach; ?>
    <p class="note">Prototype 12 · own drawing, simplified ship · local fonts (IBM Plex Mono, Barlow Condensed; OFL) · no external requests</p>
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
<script src="sunny.js"></script>
</body>
</html>
