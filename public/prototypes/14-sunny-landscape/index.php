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

/** One room: hit area with its topic and link count written into the ship. */
function zone(array $room, array $links): string
{
    [$x, $y, $w, $h] = ZONES[$room['id']]['box'];
    [$lx, $ly, $anchor, $size] = LABELS[$room['id']];
    $pts = chamfer($x, $y, $w, $h, 9);

    // A long topic breaks into two lines at its middle space.
    $topic = mb_strtoupper($room['topic']);
    $lines = [$topic];
    if (mb_strlen($topic) * $size * 0.44 > $w - 14 && str_contains($topic, ' ')) {
        $words = explode(' ', $topic);
        $half = intdiv(count($words), 2);
        $lines = [implode(' ', array_slice($words, 0, $half)), implode(' ', array_slice($words, $half))];
    }
    $text = '';
    foreach ($lines as $n => $line) {
        $text .= sprintf('<text class="zt" x="%d" y="%d" text-anchor="%s" style="font-size:%dpx">%s</text>', $lx, $ly - (count($lines) - 1 - $n) * $size * 0.95, $anchor, $size, e($line));
    }

    return sprintf(
        '<a class="zone%1$s" href="#%2$s" data-room="%2$s" aria-label="%3$s, %4$s">'
        . '<polygon class="fill" points="%5$s"/><polygon class="hatch" points="%5$s" fill="url(#hatch)"/><polygon class="edge" points="%5$s"/>'
        . '%6$s<text class="zc" x="%7$d" y="%8$d" text-anchor="%9$s">%10$s</text></a>',
        $links === [] ? ' zero' : '',
        e($room['id']),
        e($room['topic']),
        countLabel(count($links)),
        $pts,
        $text,
        $lx,
        $ly + 17,
        $anchor,
        e(strtoupper(countLabel(count($links)))),
    );
}

/** @param list<array<string, mixed>> $rooms */
function landSheet(array $rooms, array $linksByRoom): string
{
    $s = '<svg class="chart land" viewBox="' . VIEW_LAND . '" preserveAspectRatio="xMidYMid meet" role="group" aria-label="Side elevation of the ship with ten labelled rooms" focusable="false">';
    $s .= ship_drawing(false);
    foreach ($rooms as $room) {
        $s .= zone($room, $linksByRoom[$room['id']]);
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
<title>Sunny Landscape · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<?= ship_defs() ?>
<div class="first">
    <header class="top">
        <h1>Thousand Sunny</h1>
        <p>Sheet 14 · <?= $total ?> links</p>
    </header>

    <aside class="rotate" aria-label="Hint">
        <svg class="phone" viewBox="0 0 64 64" width="64" height="64" aria-hidden="true" focusable="false">
            <g class="turn"><rect x="22" y="10" width="20" height="38" rx="3"/><path d="M29 14h6M28 43h8"/></g>
            <path class="arc" d="M12 22A24 24 0 0 1 28 56M28 56l-1-7M28 56l7-3"/>
        </svg>
        <p><b>Turn your phone</b> for the ship view.<span>The topics below work in portrait, too.</span></p>
    </aside>

    <main class="stage" id="stage">
        <div class="sheetwrap" id="sheetwrap"><?= landSheet($rooms, $linksByRoom) ?></div>
    </main>

    <nav class="themes" aria-label="Topics">
        <ol>
            <?php foreach ($rooms as $room): $n = count($linksByRoom[$room['id']]); ?>
                <li><a href="#<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>"<?= $n === 0 ? ' class="zero"' : '' ?>>
                    <span class="t-txt"><b><?= e($room['topic']) ?></b><i><?= e($room['name']) ?></i></span>
                    <span class="t-cnt"><?= $n ?></span>
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
    <p class="note">Prototype 14 · own drawing, simplified ship · local fonts (IBM Plex Mono, Barlow Condensed; OFL) · no external requests</p>
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
