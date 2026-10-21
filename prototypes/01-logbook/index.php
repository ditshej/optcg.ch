<?php

declare(strict_types=1);

require __DIR__ . '/data.php';

['rooms' => $rooms, 'icon' => $icon] = prototype_data();
$total = array_sum(array_map(static fn (array $r): int => count($r['links']), $rooms));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Logbook · optcg.ch prototype</title>
<link rel="stylesheet" href="style.css">
<script src="app.js" defer></script>
</head>
<body>
<a class="skip" href="#main">Skip to links</a>

<header class="topbar">
  <a class="brand" href="./"><span class="brand-mark" aria-hidden="true"></span>optcg.ch</a>
  <span class="topbar-note">One Piece Card Game · link logbook</span>
</header>

<div class="shell">
  <nav class="side" aria-label="Rooms">
    <p class="side-label">Rooms</p>
    <ol>
<?php foreach ($rooms as $i => $room): ?>
      <li><a href="#<?= h($room['id']) ?>" data-spy="<?= h($room['id']) ?>"><span class="num"><?= sprintf('%02d', $i + 1) ?></span><?= h($room['name']) ?><span class="count"><?= count($room['links']) ?></span></a></li>
<?php endforeach; ?>
    </ol>
  </nav>

  <main id="main" class="content">
    <header class="intro">
      <p class="eyebrow">Logbook</p>
      <h1>Every link a crew needs, one room at a time.</h1>
      <p class="lede"><?= $total ?> hand-picked links for the One Piece Card Game, sorted by the rooms of the Thousand Sunny. Pick a room from the index, or just scroll.</p>
    </header>

<?php foreach ($rooms as $i => $room): ?>
    <section class="room" id="<?= h($room['id']) ?>" aria-labelledby="h-<?= h($room['id']) ?>" data-room>
      <p class="eyebrow"><?= sprintf('%02d', $i + 1) ?> · <?= h($room['topic']) ?> · <?= h($room['character']) ?></p>
      <h2 id="h-<?= h($room['id']) ?>"><?= h($room['name']) ?></h2>
      <p class="room-desc"><?= h($room['description']) ?></p>
<?php if ($room['links'] === []): ?>
      <p class="empty">No links on this deck yet.</p>
<?php else: ?>
      <ul class="links">
<?php foreach ($room['links'] as $link):
    $src = $icon($link);
    $host = preg_replace('/^www\./', '', (string) parse_url((string) $link['url'], PHP_URL_HOST));
    ?>
        <li class="reveal">
          <a class="link" href="<?= h($link['url']) ?>" rel="noopener">
            <?php if ($src !== null): ?><img class="ico" src="<?= h($src) ?>" alt="" width="44" height="44" loading="lazy"><?php else: ?><span class="ico ico-fallback" aria-hidden="true"><?= h(mb_strtoupper(mb_substr((string) $link['title'], 0, 1))) ?></span><?php endif; ?>
            <span class="txt">
              <span class="title"><?= h($link['title']) ?></span>
              <span class="desc"><?= h($link['description']) ?></span>
            </span>
            <span class="meta"><span class="host"><?= h($host) ?></span><span class="lang"><?= h(strtoupper((string) ($link['language'] ?? ''))) ?></span></span>
            <svg class="arrow" viewBox="0 0 16 16" width="16" height="16" aria-hidden="true"><path d="M4 12 12 4M5 4h7v7" fill="none" stroke="currentColor" stroke-width="1.6"/></svg>
          </a>
        </li>
<?php endforeach; ?>
      </ul>
<?php endif; ?>
    </section>
<?php endforeach; ?>

    <footer class="foot">Prototype 1 of 7 · fan-made, not affiliated with Bandai or Eiichiro Oda.</footer>
  </main>
</div>
</body>
</html>
