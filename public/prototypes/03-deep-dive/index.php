<?php

declare(strict_types=1);

require __DIR__ . '/data.php';

['rooms' => $rooms, 'icon' => $icon] = prototype_data();

// Colour of each depth layer. Two bands keep every layer far from mid-grey, so the text colour
// (dark in the sunlit band, light in the deep band) always has WCAG AA contrast:
// shallow water [176 224 236] -> [96 160 184], then deep water [16 58 84] -> [3 14 26].
$count = count($rooms);
$lightCount = max(1, (int) ceil($count * 0.4));
$blend = static fn (array $a, array $b, float $t): array => array_map(static fn (int $x, int $y): int => (int) round($x + ($y - $x) * $t), $a, $b);
$layers = [];
foreach ($rooms as $i => $room) {
    $light = $i < $lightCount;
    $n = $light ? $lightCount : $count - $lightCount;
    $k = $light ? $i : $i - $lightCount;
    $t = $n > 1 ? $k / ($n - 1) : 0;
    $rgb = $light ? $blend([176, 224, 236], [96, 160, 184], $t) : $blend([16, 58, 84], [3, 14, 26], $t);
    $layers[] = [
        'bg' => sprintf('rgb(%d %d %d)', ...$rgb),
        'dark' => $light, // light water: dark text
        'depth' => (int) round($i * 120 + ($i > 0 ? 30 : 0)),
    ];
}
// One continuous gradient behind the whole journey, aligned to the layer colours.
$stops = [];
foreach ($layers as $i => $layer) {
    $stops[] = $layer['bg'] . ' ' . round(($i + 0.5) / $count * 100, 2) . '%';
}
$gradient = 'linear-gradient(to bottom, ' . implode(', ', $stops) . ')';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Deep Dive · optcg.ch prototype</title>
<link rel="stylesheet" href="style.css">
<script src="app.js" defer></script>
</head>
<body>
<a class="skip" href="#dive">Skip to links</a>

<nav class="gauge" aria-label="Depth">
  <ol>
<?php foreach ($rooms as $i => $room): ?>
    <li style="--at: <?= round(($i + 0.5) / $count * 100, 2) ?>%"><a href="#<?= h($room['id']) ?>"><span class="gauge-m"><?= $layers[$i]['depth'] ?> m</span><span class="gauge-n"><?= h($room['name']) ?></span></a></li>
<?php endforeach; ?>
  </ol>
  <span class="gauge-mark" aria-hidden="true"></span>
</nav>

<header class="surface">
  <svg class="waves" viewBox="0 0 1440 160" preserveAspectRatio="none" aria-hidden="true">
    <path class="w1" d="M0 80 C 120 40 240 40 360 80 S 600 120 720 80 S 960 40 1080 80 S 1320 120 1440 80 L1440 160 L0 160Z"/>
    <path class="w2" d="M0 110 C 120 80 240 80 360 110 S 600 140 720 110 S 960 80 1080 110 S 1320 140 1440 110 L1440 160 L0 160Z"/>
  </svg>
  <div class="surface-in">
    <p class="kicker">optcg.ch · One Piece Card Game</p>
    <h1>Dive in.</h1>
    <p class="lede">Ten rooms, one ship, and a lot of links. Sink through the decks and every room gets a little darker and a little quieter.</p>
    <a class="down" href="#<?= h($rooms[0]['id']) ?>">Start at the surface <span aria-hidden="true">↓</span></a>
  </div>
</header>

<main id="dive" class="dive" style="background: <?= h($gradient) ?>">
<?php foreach ($rooms as $i => $room): $L = $layers[$i]; ?>
  <section class="layer <?= $L['dark'] ? 'on-light' : 'on-dark' ?>" id="<?= h($room['id']) ?>" aria-labelledby="h-<?= h($room['id']) ?>" data-layer>
    <div class="layer-in">
      <p class="depth"><span><?= $L['depth'] ?></span> m · <?= h($room['topic']) ?></p>
      <h2 id="h-<?= h($room['id']) ?>"><?= h($room['name']) ?></h2>
      <p class="who"><?= h($room['character']) ?>'s deck</p>
      <p class="desc"><?= h($room['description']) ?></p>
<?php if ($room['links'] === []): ?>
      <p class="empty">Nothing down here yet.</p>
<?php else: ?>
      <ul class="links">
<?php foreach ($room['links'] as $link):
    $src = $icon($link);
    $host = preg_replace('/^www\./', '', (string) parse_url((string) $link['url'], PHP_URL_HOST));
    ?>
        <li class="rise">
          <a href="<?= h($link['url']) ?>" rel="noopener">
            <?php if ($src !== null): ?><img src="<?= h($src) ?>" alt="" width="48" height="48" loading="lazy"><?php else: ?><span class="fb" aria-hidden="true"><?= h(mb_strtoupper(mb_substr((string) $link['title'], 0, 1))) ?></span><?php endif; ?>
            <span class="t"><strong><?= h($link['title']) ?></strong><span><?= h($link['description']) ?></span><em><?= h($host) ?></em></span>
          </a>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>
  <footer class="seabed">
    <p>You reached the bottom.</p>
    <a href="#top-of-page" class="up">Back to the surface ↑</a>
    <small>Prototype 3 of 7 · fan-made, not affiliated with Bandai or Eiichiro Oda.</small>
  </footer>
</main>
<span id="top-of-page"></span>
</body>
</html>
