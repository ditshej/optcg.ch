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
<meta name="color-scheme" content="light dark">
<title>Harbour Grid · optcg.ch prototype</title>
<link rel="stylesheet" href="style.css">
<script src="app.js" defer></script>
</head>
<body>
<a class="skip" href="#grid">Skip to rooms</a>

<header class="bar">
  <a class="brand" href="./"><svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><path d="M12 3v14M7 8h10M4 14c0 4 3.5 7 8 7s8-3 8-7M4 14l2.5 1M20 14l-2.5 1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>optcg.ch</a>
  <button type="button" class="theme" id="theme" aria-label="Switch colour theme"><span class="theme-dot" aria-hidden="true"></span><span class="theme-text">Theme</span></button>
</header>

<main>
  <div class="hero">
    <h1>Links for the<br>One Piece Card Game.</h1>
    <p><?= $total ?> links in <?= count($rooms) ?> rooms. Open a room to see what is inside.</p>
  </div>

  <div class="grid" id="grid">
<?php foreach ($rooms as $i => $room):
    $n = $i + 1;
    $withIcons = array_values(array_filter(array_map(static fn (array $l): ?string => $icon($l), $room['links'])));
    ?>
    <details class="tile t<?= $n ?>" name="rooms" id="<?= h($room['id']) ?>">
      <summary>
        <span class="bg" aria-hidden="true"></span>
        <span class="face">
          <span class="top"><span class="no"><?= sprintf('%02d', $n) ?></span><span class="badge"><?= count($room['links']) ?> <?= count($room['links']) === 1 ? 'link' : 'links' ?></span></span>
          <span class="mid">
            <span class="name"><?= h($room['name']) ?></span>
            <span class="sub"><?= h($room['topic']) ?> · <?= h($room['character']) ?></span>
            <span class="blurb"><?= h($room['description']) ?></span>
          </span>
          <span class="stack" aria-hidden="true"><?php foreach (array_slice($withIcons, 0, 4) as $src): ?><img src="<?= h($src) ?>" alt="" width="32" height="32" loading="lazy"><?php endforeach; ?></span>
          <span class="chev" aria-hidden="true"><svg viewBox="0 0 16 16" width="16" height="16"><path d="M3 6l5 5 5-5" fill="none" stroke="currentColor" stroke-width="2"/></svg></span>
        </span>
      </summary>
      <div class="panel">
<?php if ($room['links'] === []): ?>
        <p class="empty">No links in this room yet.</p>
<?php else: ?>
        <ul>
<?php foreach ($room['links'] as $link):
    $src = $icon($link);
    $host = preg_replace('/^www\./', '', (string) parse_url((string) $link['url'], PHP_URL_HOST));
    ?>
          <li><a href="<?= h($link['url']) ?>" rel="noopener">
            <?php if ($src !== null): ?><img src="<?= h($src) ?>" alt="" width="40" height="40" loading="lazy"><?php else: ?><span class="fb" aria-hidden="true"><?= h(mb_strtoupper(mb_substr((string) $link['title'], 0, 1))) ?></span><?php endif; ?>
            <span class="lt"><strong><?= h($link['title']) ?></strong><span><?= h($link['description']) ?></span><em><?= h($host) ?></em></span>
          </a></li>
<?php endforeach; ?>
        </ul>
<?php endif; ?>
      </div>
    </details>
<?php endforeach; ?>
  </div>
</main>

<footer class="foot">Prototype 5 of 7 · fan-made, not affiliated with Bandai or Eiichiro Oda.</footer>
</body>
</html>
