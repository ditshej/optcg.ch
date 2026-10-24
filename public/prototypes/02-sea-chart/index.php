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

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Place of a room on the chart. Hand-set for ten rooms, zigzag after that. */
function place(int $i, bool $mobile): array
{
    $desktop = [[100, 330], [215, 160], [350, 285], [335, 470], [495, 150], [565, 385], [695, 245], [705, 490], [855, 150], [885, 380]];
    $phone = [[110, 95], [290, 175], [110, 255], [290, 335], [110, 415], [290, 495], [110, 575], [290, 655], [110, 735], [290, 815]];
    $set = $mobile ? $phone : $desktop;
    if (isset($set[$i])) {
        return $set[$i];
    }

    return $mobile ? [$i % 2 ? 290 : 110, 95 + 80 * $i] : [100 + 90 * ($i % 9), $i % 2 ? 480 : 160];
}

/** Smooth closed island outline around a centre; the seed keeps it stable between requests. */
function blob(float $cx, float $cy, float $r, int $seed): string
{
    mt_srand($seed);
    $n = 9;
    $pts = [];
    for ($k = 0; $k < $n; $k++) {
        $a = 2 * M_PI * $k / $n;
        $rad = $r * (0.68 + 0.5 * mt_rand(0, 100) / 100);
        $pts[] = [$cx + $rad * cos($a), $cy + $rad * sin($a) * 0.82];
    }
    $mid = static fn (array $a, array $b): array => [($a[0] + $b[0]) / 2, ($a[1] + $b[1]) / 2];
    $start = $mid($pts[$n - 1], $pts[0]);
    $d = sprintf('M%.1f %.1f', $start[0], $start[1]);
    for ($k = 0; $k < $n; $k++) {
        $m = $mid($pts[$k], $pts[($k + 1) % $n]);
        $d .= sprintf(' Q%.1f %.1f %.1f %.1f', $pts[$k][0], $pts[$k][1], $m[0], $m[1]);
    }

    return $d . 'Z';
}

/** Smooth open curve through the given points (Catmull-Rom turned into cubic segments). */
function curve(array $p): string
{
    $d = sprintf('M%.1f %.1f', $p[0][0], $p[0][1]);
    $c = count($p);
    for ($k = 0; $k < $c - 1; $k++) {
        $p0 = $p[max(0, $k - 1)];
        $p1 = $p[$k];
        $p2 = $p[$k + 1];
        $p3 = $p[min($c - 1, $k + 2)];
        $d .= sprintf(
            ' C%.1f %.1f %.1f %.1f %.1f %.1f',
            $p1[0] + ($p2[0] - $p0[0]) / 6,
            $p1[1] + ($p2[1] - $p0[1]) / 6,
            $p2[0] - ($p3[0] - $p1[0]) / 6,
            $p2[1] - ($p3[1] - $p1[1]) / 6,
            $p2[0],
            $p2[1],
        );
    }

    return $d;
}

/** Water lines: gentle waves across the chart, drawn once, with a deterministic spread. */
function waves(int $w, int $h, int $count, int $seed): string
{
    mt_srand($seed);
    $out = '';
    for ($k = 0; $k < $count; $k++) {
        $y = 40 + ($h - 80) * ($k + 0.5) / $count;
        $pts = [];
        for ($x = 20; $x <= $w - 20; $x += 140) {
            $pts[] = [$x, $y + mt_rand(-26, 26)];
        }
        $out .= sprintf('<path class="wave draw" pathLength="1" style="--d:%.2fs" d="%s"/>', 0.3 + $k * 0.12, curve($pts));
    }

    return $out;
}

function chart(array $rooms, array $linksByRoom, bool $mobile): string
{
    [$w, $h] = $mobile ? [400, 960] : [1000, 640];
    $home = $mobile ? [60, 925] : [60, 585];
    $compass = $mobile ? [335, 50] : [925, 565];
    $variant = $mobile ? 'm' : 'd';
    $pts = [];
    foreach ($rooms as $i => $_) {
        $pts[] = place($i, $mobile);
    }

    $svg = sprintf('<svg class="chart chart-%s" viewBox="0 0 %d %d" role="group" aria-label="Sea chart with %d places" focusable="false">', $variant, $w, $h, count($rooms));

    // Frame, graticule and soundings.
    $svg .= sprintf('<rect class="frame-out" x="6" y="6" width="%d" height="%d"/><rect class="frame-in" x="16" y="16" width="%d" height="%d"/>', $w - 12, $h - 12, $w - 32, $h - 32);
    $svg .= '<g class="graticule">';
    for ($x = 100; $x < $w - 20; $x += 100) {
        $svg .= sprintf('<path d="M%d 16V%d"/>', $x, $h - 16);
    }
    for ($y = 100; $y < $h - 20; $y += 100) {
        $svg .= sprintf('<path d="M16 %dH%d"/>', $y, $w - 16);
    }
    $svg .= '</g><g class="ticks">';
    for ($x = 16; $x <= $w - 16; $x += 20) {
        $svg .= sprintf('<path d="M%d 16v%d M%d %dv-%d"/>', $x, $x % 100 === 16 ? 10 : 5, $x, $h - 16, $x % 100 === 16 ? 10 : 5);
    }
    for ($y = 16; $y <= $h - 16; $y += 20) {
        $svg .= sprintf('<path d="M16 %dh%d M%d %dh-%d"/>', $y, $y % 100 === 16 ? 10 : 5, $w - 16, $y, $y % 100 === 16 ? 10 : 5);
    }
    $svg .= '</g>';
    $svg .= '<g class="waves">' . waves($w, $h, $mobile ? 9 : 6, $mobile ? 7 : 3) . '</g>';

    mt_srand(11);
    $svg .= '<g class="soundings" aria-hidden="true">';
    for ($k = 0; $k < ($mobile ? 22 : 30); $k++) {
        $sx = mt_rand(40, $w - 40);
        $sy = mt_rand(40, $h - 40);
        $near = false;
        foreach ($pts as $p) {
            if (abs($p[0] - $sx) < 95 && $sy > $p[1] - 60 && $sy < $p[1] + 100) {
                $near = true;
            }
        }
        if (abs($home[0] - $sx) < 120 && abs($home[1] - $sy) < 50) {
            $near = true;
        }
        if (!$near) {
            $svg .= sprintf('<text x="%d" y="%d">%d</text>', $sx, $sy, mt_rand(4, 48));
        }
    }
    $svg .= '</g>';

    // Main course through all places, then the hover routes.
    $svg .= '<path class="course draw" pathLength="1" style="--d:.9s" d="' . curve(array_merge([$home], $pts)) . '"/>';
    $svg .= '<g class="routes">';
    foreach ($rooms as $i => $room) {
        [$x, $y] = $pts[$i];
        $bend = $mobile ? [($home[0] + $x) / 2 + ($i % 2 ? 60 : -40), ($home[1] + $y) / 2] : [($home[0] + $x) / 2 + 30, ($home[1] + $y) / 2 + ($i % 2 ? -60 : 40)];
        $svg .= sprintf('<path class="route" pathLength="1" data-room="%s" d="%s"/>', e($room['id']), curve([$home, $bend, [$x, $y]]));
    }
    $svg .= '</g>';

    // Home port and compass rose.
    $svg .= sprintf('<g class="home" transform="translate(%d %d)"><circle r="7"/><path d="M0 -14V14M-14 0H14"/><text x="16" y="-12">Home port</text></g>', $home[0], $home[1]);
    $svg .= sprintf('<g class="compass" aria-hidden="true" transform="translate(%d %d) scale(%s)"><circle r="46"/><circle r="38"/><path class="star" d="M0 -64L9 -9L64 0L9 9L0 64L-9 9L-64 0L-9 -9Z"/><path class="star2" d="M0 -40L5 -5L40 0L5 5L0 40L-5 5L-40 0L-5 -5Z" transform="rotate(45)"/><text y="-72" text-anchor="middle">N</text></g>', $compass[0], $compass[1], $mobile ? '.55' : '.8');

    // Places.
    foreach ($rooms as $i => $room) {
        [$x, $y] = $pts[$i];
        $count = count($linksByRoom[$room['id']]);
        $svg .= sprintf(
            '<a class="place" href="#%1$s" data-room="%1$s" aria-label="%2$s, %3$s, %4$d links"><circle class="hit" cx="%5$d" cy="%6$d" r="46"/>'
            . '<path class="shore" d="%7$s"/><path class="land" d="%8$s"/>'
            . '<circle class="pin" cx="%5$d" cy="%6$d" r="14"/><text class="num" x="%5$d" y="%9$d" text-anchor="middle">%10$d</text>'
            . '<text class="name" x="%5$d" y="%11$d" text-anchor="middle">%2$s</text><text class="topic" x="%5$d" y="%12$d" text-anchor="middle">%3$s</text></a>',
            e($room['id']),
            e($room['name']),
            e($room['topic']),
            $count,
            $x,
            $y,
            blob($x, $y, 50, 100 + $i),
            blob($x, $y, 40, 200 + $i),
            $y + 5,
            $i + 1,
            $y + 68,
            $y + 86,
        );
    }

    return $svg . '</svg>';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sea Chart · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="masthead">
    <p class="kicker">Chart no. 10 · Thousand Sunny</p>
    <h1>The One Piece Card Game<br><em>Sea Chart</em></h1>
    <p class="lede">Ten places, one course. Pick a place to see its links.</p>
</header>

<main>
    <div class="sheet">
        <?= chart($rooms, $linksByRoom, false) ?>
        <?= chart($rooms, $linksByRoom, true) ?>
    </div>

    <nav class="legend" aria-label="Places on the chart">
        <h2>Legend</h2>
        <ol>
            <?php foreach ($rooms as $i => $room): ?>
                <li><a href="#<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>"><span class="n"><?= $i + 1 ?></span><span class="t"><b><?= e($room['name']) ?></b><i><?= e($room['topic']) ?> · <?= e($room['character']) ?></i></span><span class="c"><?= count($linksByRoom[$room['id']]) ?> <?= count($linksByRoom[$room['id']]) === 1 ? 'link' : 'links' ?></span></a></li>
            <?php endforeach; ?>
        </ol>
    </nav>

    <div class="rooms">
        <?php foreach ($rooms as $room): ?>
            <section class="room" id="<?= e($room['id']) ?>" aria-labelledby="h-<?= e($room['id']) ?>">
                <p class="room-meta"><?= e($room['topic']) ?> · <?= e($room['character']) ?></p>
                <h2 id="h-<?= e($room['id']) ?>"><?= e($room['name']) ?></h2>
                <p class="room-desc"><?= e($room['description']) ?></p>
                <?php if ($linksByRoom[$room['id']] === []): ?>
                    <p class="empty">Uncharted waters: no links here yet.</p>
                <?php else: ?>
                    <ul class="links">
                        <?php foreach ($linksByRoom[$room['id']] as $link): ?>
                            <li><a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                                <?php if ($icons->hasIcon($link)): ?>
                                    <img src="../../icons/<?= e($icons->filename($link)) ?>" alt="" width="44" height="44" loading="lazy">
                                <?php else: ?>
                                    <span class="fallback" aria-hidden="true"><?= e($icons->initial($link)) ?></span>
                                <?php endif; ?>
                                <span class="txt"><b><?= e($link['title']) ?></b><span><?= e($link['description']) ?></span></span>
                                <span class="lang"><?= e(strtoupper((string) ($link['language'] ?? ''))) ?></span>
                            </a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
    </div>
</main>

<footer>
    <p>Prototype 2 of 7 · own drawing, local fonts (IM Fell, OFL) · no external requests</p>
</footer>

<dialog class="note" id="note" aria-label="Place details">
    <form method="dialog"><button class="close" aria-label="Close">&times;</button></form>
    <div class="note-body"></div>
</dialog>
<script src="chart.js"></script>
</body>
</html>
