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

/**
 * Compartments of the abstract cross-section: room id => [x, y, width, height].
 * A room that has no slot here still shows up in the parts list and the detail view.
 */
const SLOTS = [
    'crows-nest' => [258, 28, 124, 72],
    'figurehead' => [90, 146, 160, 80],
    'helm' => [390, 146, 160, 80],
    'navigation-room' => [90, 252, 220, 98],
    'library' => [330, 252, 220, 98],
    'galley' => [115, 372, 130, 88],
    'aquarium-bar' => [255, 372, 130, 88],
    'sick-bay' => [395, 372, 130, 88],
    'usopp-factory' => [182, 472, 136, 68],
    'soldier-dock' => [326, 472, 136, 68],
];

function section(array $rooms): string
{
    $svg = '<svg class="section" viewBox="0 0 680 760" role="group" aria-label="Cross-section of a ship with ' . count(SLOTS) . ' compartments" focusable="false">';
    $svg .= '<defs><pattern id="hatch" width="8" height="8" patternUnits="userSpaceOnUse" patternTransform="rotate(45)"><path d="M0 0V8" stroke="currentColor" stroke-width="1"/></pattern>'
        . '<marker id="arrow" viewBox="0 0 10 10" refX="9" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse"><path d="M0 1L10 5L0 9Z" fill="currentColor"/></marker></defs>';

    // Centre line, mast, rigging, hull, deck, waterline: the drawing proper.
    $svg .= '<g class="draw-lines">';
    $svg .= '<path class="centre" d="M320 8V690"/>';
    $svg .= '<path class="mast" d="M313 100V600M327 100V600"/>';
    $svg .= '<path class="rigging" d="M272 64L70 236M368 64L570 236M320 12L120 236M320 12L520 236"/>';
    $svg .= '<path class="hull" d="M70 236C58 420 130 610 320 650C510 610 582 420 570 236Z"/>';
    $svg .= '<path class="hull" d="M54 236H586"/>';
    $svg .= '<path class="keel" d="M300 650H340V664H300Z"/>';
    $svg .= '<path class="water" d="M20 562c20-8 40 8 60 0s40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0 40-8 60 0 40 8 60 0"/>';
    $svg .= '<path class="water below" d="M20 580c20-8 40 8 60 0s40-8 60 0"/>';
    $svg .= '</g>';

    // Dimension lines.
    $svg .= '<g class="dims" aria-hidden="true">';
    $svg .= '<path d="M614 236V664" marker-start="url(#arrow)" marker-end="url(#arrow)"/><path d="M590 236H626M590 664H626"/>';
    $svg .= '<text transform="translate(640 450) rotate(90)" text-anchor="middle">DEPTH 428</text>';
    $svg .= '<path d="M70 706H570" marker-start="url(#arrow)" marker-end="url(#arrow)"/><path d="M70 690V718M570 690V718"/>';
    $svg .= '<text x="320" y="732" text-anchor="middle">BEAM 500</text>';
    $svg .= '<text x="26" y="563" class="wl">WL</text>';
    $svg .= '</g>';

    $n = 0;
    foreach ($rooms as $room) {
        $n++;
        if (!isset(SLOTS[$room['id']])) {
            continue;
        }
        [$x, $y, $w, $h] = SLOTS[$room['id']];
        $svg .= sprintf(
            '<a class="cell" href="#%1$s" data-room="%1$s" aria-label="%2$s, room %3$02d"><rect class="fill" x="%4$d" y="%5$d" width="%6$d" height="%7$d"/>'
            . '<rect class="hatch" x="%4$d" y="%5$d" width="%6$d" height="%7$d" fill="url(#hatch)"/><rect class="edge" x="%4$d" y="%5$d" width="%6$d" height="%7$d"/>'
            . '<text class="no" x="%8$d" y="%9$d">%3$02d</text><text class="nm" x="%10$s" y="%11$s" text-anchor="middle">%12$s</text></a>',
            e($room['id']),
            e($room['name']),
            $n,
            $x,
            $y,
            $w,
            $h,
            $x + 8,
            $y + 19,
            $x + $w / 2,
            $y + $h / 2 + 12,
            e(mb_strtoupper($room['name'])),
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
<title>Blueprint · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page" id="page">
    <header class="titleblock">
        <div class="tb-main">
            <p class="tb-k">Sheet 04 · Section A–A · Scale 1:20</p>
            <h1>Thousand<br>Sunny</h1>
            <p class="tb-s">Link plan for the One Piece Card Game. Select a compartment to open its detail sheet.</p>
        </div>
        <dl class="tb-meta">
            <div><dt>Drawing</dt><dd>optcg.ch</dd></div>
            <div><dt>Compartments</dt><dd><?= count($rooms) ?></dd></div>
            <div><dt>Links</dt><dd><?= array_sum(array_map('count', $linksByRoom)) ?></dd></div>
            <div><dt>Status</dt><dd>Prototype</dd></div>
        </dl>
    </header>

    <main class="stage">
        <div class="drawing"><?= section($rooms) ?></div>

        <nav class="parts" aria-label="Parts list">
            <h2>Parts list</h2>
            <ol>
                <?php foreach ($rooms as $i => $room): ?>
                    <li><a href="#<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>">
                        <span class="p-no"><?= sprintf('%02d', $i + 1) ?></span>
                        <span class="p-name"><?= e($room['name']) ?></span>
                        <span class="p-sub"><?= e($room['topic']) ?> · <?= e($room['character']) ?></span>
                        <span class="p-cnt"><?= count($linksByRoom[$room['id']]) ?></span>
                    </a></li>
                <?php endforeach; ?>
            </ol>
        </nav>
    </main>

    <footer>Prototype 4 of 7 · own drawing, abstract ship · local fonts (IBM Plex Mono, Barlow Condensed; OFL) · no external requests</footer>
</div>

<div class="details">
    <?php foreach ($rooms as $i => $room): ?>
        <section class="room" id="<?= e($room['id']) ?>" aria-labelledby="h-<?= e($room['id']) ?>" tabindex="-1">
            <header class="room-head">
                <p class="room-k">Detail <?= sprintf('%02d', $i + 1) ?> · <?= e($room['topic']) ?> · <?= e($room['character']) ?></p>
                <h2 id="h-<?= e($room['id']) ?>"><?= e($room['name']) ?></h2>
                <p class="room-d"><?= e($room['description']) ?></p>
                <button class="back" type="button" data-close>Back to section <kbd>Esc</kbd></button>
            </header>
            <?php if ($linksByRoom[$room['id']] === []): ?>
                <p class="empty">No links assigned to this compartment yet.</p>
            <?php else: ?>
                <ol class="links">
                    <?php foreach ($linksByRoom[$room['id']] as $k => $link): ?>
                        <li><a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                            <span class="l-no"><?= sprintf('%02d', $k + 1) ?></span>
                            <?php if ($icons->hasIcon($link)): ?>
                                <img src="../../icons/<?= e($icons->filename($link)) ?>" alt="" width="44" height="44" loading="lazy">
                            <?php else: ?>
                                <span class="fallback" aria-hidden="true"><?= e($icons->initial($link)) ?></span>
                            <?php endif; ?>
                            <span class="l-txt"><b><?= e($link['title']) ?></b><span><?= e($link['description']) ?></span></span>
                            <span class="l-lang"><?= e(strtoupper((string) ($link['language'] ?? ''))) ?></span>
                        </a></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
<script src="blueprint.js"></script>
</body>
</html>
