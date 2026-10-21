<?php

declare(strict_types=1);

$root = dirname(__DIR__, 2);
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

/** Card colour per room; all dark enough for cream text (WCAG AA). */
const ACCENTS = [
    'figurehead' => '#a8322a',
    'navigation-room' => '#1f6384',
    'crows-nest' => '#3c6e35',
    'library' => '#7a4a1d',
    'usopp-factory' => '#85650f',
    'galley' => '#b0501a',
    'sick-bay' => '#a43b57',
    'soldier-dock' => '#4b5563',
    'aquarium-bar' => '#0f6b6b',
    'helm' => '#3b4a8c',
];

/** Own card art: simple line drawings, one per room, drawn in a 200 x 120 window. */
function art(string $id): string
{
    switch ($id) {
        case 'figurehead':
            $rays = '';
            for ($k = 0; $k < 12; $k++) {
                $a = $k * M_PI / 6;
                $rays .= sprintf('M%.1f %.1fL%.1f %.1f', 100 + 34 * cos($a), 60 + 34 * sin($a), 100 + 50 * cos($a), 60 + 50 * sin($a));
            }

            return '<circle cx="100" cy="60" r="24"/><path d="' . $rays . '"/><path d="M90 56h.1M110 56h.1M89 68Q100 78 111 68"/>';
        case 'navigation-room':
            return '<circle cx="100" cy="60" r="44"/><circle cx="100" cy="60" r="36" stroke-width="1.5"/><path d="M100 22L113 60L87 60Z" class="solid"/><path d="M100 98L113 60L87 60Z"/>';
        case 'crows-nest':
            return '<path d="M100 14V104"/><path d="M68 42H132L123 62H77Z"/><path d="M100 14L130 23L100 32" class="solid"/><path d="M36 104q16-10 32 0t32 0 32 0 32 0"/>';
        case 'library':
            return '<path d="M40 94H168"/><rect x="50" y="42" width="22" height="52"/><rect x="76" y="30" width="24" height="64"/><rect x="104" y="46" width="22" height="48"/><rect x="132" y="40" width="22" height="54" transform="rotate(12 143 94)"/><path d="M82 44H94M82 80H94M56 54H66" stroke-width="2"/>';
        case 'usopp-factory':
            return '<circle cx="100" cy="60" r="34" stroke-width="12" stroke-dasharray="10 7.8"/><circle cx="100" cy="60" r="26"/><circle cx="100" cy="60" r="10"/>';
        case 'galley':
            return '<path d="M66 54H134V86Q134 98 122 98H78Q66 98 66 86Z"/><path d="M58 54H142M66 64H52M134 64H148"/><path d="M86 42q-8-9 0-18M100 42q-8-9 0-18M114 42q-8-9 0-18"/>';
        case 'sick-bay':
            return '<circle cx="100" cy="60" r="46"/><path d="M88 30H112V48H130V72H112V90H88V72H70V48H88Z" class="solid"/>';
        case 'soldier-dock':
            return '<circle cx="100" cy="26" r="9"/><path d="M100 35V98M80 50H120M60 68Q66 98 100 98Q134 98 140 68M60 68L54 80M60 68L72 76M140 68L146 80M140 68L128 76"/>';
        case 'aquarium-bar':
            return '<path d="M48 62Q88 24 128 62Q88 100 48 62Z"/><path d="M128 62L160 40V84Z" class="solid"/><circle cx="76" cy="57" r="3.5" class="solid"/><circle cx="150" cy="24" r="6"/><circle cx="166" cy="44" r="3.5"/><circle cx="140" cy="12" r="3.5"/>';
        case 'helm':
            $spokes = '';
            for ($k = 0; $k < 8; $k++) {
                $a = $k * M_PI / 4;
                $spokes .= sprintf('M%.1f %.1fL%.1f %.1f', 100 + 10 * cos($a), 60 + 10 * sin($a), 100 + 50 * cos($a), 60 + 50 * sin($a));
            }

            return '<circle cx="100" cy="60" r="32"/><circle cx="100" cy="60" r="9"/><path d="' . $spokes . '" stroke-width="5" stroke-linecap="round"/>';
        default:
            return '<rect x="64" y="22" width="72" height="76" rx="6"/><path d="M80 50H120M80 66H120"/>';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Card Table · optcg.ch prototype</title>
<script>document.documentElement.classList.add('js')</script>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header class="head">
    <p class="kicker">optcg.ch · Thousand Sunny</p>
    <h1>The Crew<br>Deck</h1>
    <p class="lede">Ten room cards for the One Piece Card Game. Turn a card over to see its links.</p>
</header>

<main class="table">
    <?php foreach ($rooms as $i => $room): $id = (string) $room['id']; $links = $linksByRoom[$id]; $n = count($links); ?>
        <article class="card" id="<?= e($id) ?>" style="--accent: <?= e(ACCENTS[$id] ?? '#4b5563') ?>; --i: <?= $i ?>" aria-labelledby="t-<?= e($id) ?>">
            <div class="tilt"><div class="flipper">
                <div class="face front">
                    <div class="frame">
                        <div class="top">
                            <span class="cost" title="<?= $n ?> links"><?= $n ?><small>links</small></span>
                            <h2 id="t-<?= e($id) ?>"><?= e($room['name']) ?></h2>
                        </div>
                        <div class="art"><svg viewBox="0 0 200 120" aria-hidden="true" focusable="false"><?= art($id) ?></svg></div>
                        <p class="type">Room <span aria-hidden="true">◆</span> <?= e($room['topic']) ?></p>
                        <div class="text"><p><?= e($room['description']) ?></p><?php if ($n > 0): ?><ul class="peek" aria-hidden="true"><?php foreach (array_slice($links, 0, 3) as $link): ?><li><?= e($link['title']) ?></li><?php endforeach; ?><?php if ($n > 3): ?><li class="more">+ <?= $n - 3 ?> more</li><?php endif; ?></ul><?php endif; ?></div>
                        <p class="foot"><span><?= e($room['character']) ?></span><span><?= sprintf('%02d/%02d', $i + 1, count($rooms)) ?></span></p>
                    </div>
                </div>
                <div class="face back" role="group" aria-label="<?= e($room['name']) ?> links">
                    <div class="frame">
                        <div class="top"><h2><?= e($room['name']) ?></h2></div>
                        <?php if ($links === []): ?>
                            <p class="empty">No links dealt to this card yet.</p>
                        <?php else: ?>
                            <ul class="links">
                                <?php foreach ($links as $link): ?>
                                    <li><a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer">
                                        <?php if ($icons->hasIcon($link)): ?>
                                            <img src="../../public/icons/<?= e($icons->filename($link)) ?>" alt="" width="44" height="44" loading="lazy">
                                        <?php else: ?>
                                            <span class="fallback" aria-hidden="true"><?= e($icons->initial($link)) ?></span>
                                        <?php endif; ?>
                                        <span class="txt"><b><?= e($link['title']) ?></b><span><?= e($link['description']) ?></span></span>
                                    </a></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div></div>
        </article>
    <?php endforeach; ?>
</main>

<footer>Prototype 6 of 7 · own card frame and drawings · local fonts (Alfa Slab One, Source Sans 3; OFL) · no external requests</footer>
<script src="cards.js"></script>
</body>
</html>
