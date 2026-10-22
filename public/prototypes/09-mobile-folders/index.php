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

/** Number of links shown as mini icons on a tile; the rest goes behind "+N". */
const PREVIEW = 3;

function e(mixed $v): string
{
    return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Icon or initial tile for one link, size set in CSS. */
function icon(IconResolver $icons, array $link): string
{
    if ($icons->hasIcon($link)) {
        return '<img src="../../icons/' . e($icons->filename($link)) . '" alt="" width="44" height="44" loading="lazy">';
    }

    return '<span class="fallback" aria-hidden="true">' . e($icons->initial($link)) . '</span>';
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Folders · optcg.ch mobile prototype 09</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<div class="page" id="page">
    <header class="titleblock">
        <p class="tb-k">Sheet 09 · Folders · Scale 1:1</p>
        <h1>Thousand Sunny</h1>
        <p class="tb-s">Tap an icon to go straight to the link. Tap a room name or the +N field for the whole room.</p>
        <p class="tb-meta"><span><?= count($rooms) ?> rooms</span><span><?= array_sum(array_map('count', $linksByRoom)) ?> links</span></p>
    </header>

    <main>
        <ol class="grid">
            <?php foreach ($rooms as $i => $room):
                $links = $linksByRoom[$room['id']];
                $shown = array_slice($links, 0, PREVIEW);
                $more = count($links) - PREVIEW;
                ?>
                <li class="tile">
                    <a class="tile-head" href="#<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>" aria-label="<?= e($room['name']) ?>, all <?= count($links) ?> links">
                        <span class="t-no"><?= sprintf('%02d', $i + 1) ?></span>
                        <span class="t-name"><?= e($room['name']) ?></span>
                        <span class="t-sub"><?= e($room['character']) ?></span>
                    </a>
                    <?php if ($links === []): ?>
                        <div class="tile-body"><p class="none">No links yet.</p></div>
                    <?php else: ?>
                    <div class="tile-body">
                        <ul class="minis">
                            <?php foreach ($shown as $link): ?>
                                <li><a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer" aria-label="<?= e($link['title']) ?>" title="<?= e($link['title']) ?>"><?= icon($icons, $link) ?></a></li>
                            <?php endforeach; ?>
                            <?php if ($more > 0): ?>
                                <li><a class="more" href="#<?= e($room['id']) ?>" data-room="<?= e($room['id']) ?>" aria-label="Show <?= $more ?> more links in <?= e($room['name']) ?>">+<?= $more ?></a></li>
                            <?php else: ?>
                                <?php for ($k = count($shown); $k < PREVIEW + 1; $k++): ?>
                                    <li class="slot" aria-hidden="true"></li>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </ul>
                        <p class="count" aria-hidden="true"><b><?= count($links) ?></b><span><?= count($links) === 1 ? 'link' : 'links' ?></span></p>
                    </div>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </main>

    <footer>Prototype 09 of the mobile set · own drawing · local fonts (IBM Plex Mono, Barlow Condensed; OFL) · no external requests</footer>
</div>

<div class="sheets">
    <div class="scrim" data-close hidden></div>
    <?php foreach ($rooms as $i => $room): ?>
        <section class="sheet" id="<?= e($room['id']) ?>" role="dialog" aria-modal="true" aria-labelledby="h-<?= e($room['id']) ?>" tabindex="-1">
            <header class="sheet-head" data-drag>
                <span class="grip" aria-hidden="true"></span>
                <p class="sheet-k">Detail <?= sprintf('%02d', $i + 1) ?> · <?= e($room['topic']) ?> · <?= e($room['character']) ?></p>
                <h2 id="h-<?= e($room['id']) ?>"><?= e($room['name']) ?></h2>
                <p class="sheet-d"><?= e($room['description']) ?></p>
                <button class="close" type="button" data-close aria-label="Close">Close</button>
            </header>
            <?php if ($linksByRoom[$room['id']] === []): ?>
                <p class="empty">No links assigned to this room yet.</p>
            <?php else: ?>
                <ol class="links">
                    <?php foreach ($linksByRoom[$room['id']] as $k => $link): ?>
                        <li><a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                            <?= icon($icons, $link) ?>
                            <span class="l-txt"><b><?= e($link['title']) ?></b><span><?= e($link['description']) ?></span></span>
                            <span class="l-lang"><?= e(strtoupper((string) ($link['language'] ?? ''))) ?></span>
                        </a></li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</div>
<script src="folders.js"></script>
</body>
</html>
