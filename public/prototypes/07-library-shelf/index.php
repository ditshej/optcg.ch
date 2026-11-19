<?php

declare(strict_types=1);

require __DIR__ . '/data.php';

['rooms' => $rooms, 'icon' => $icon] = prototype_data();
$total = array_sum(array_map(static fn (array $r): int => count($r['links']), $rooms));

// Spine looks: cloth colour, gilt colour, height (rem), width (rem). Cycles if there are more rooms.
$looks = [
    ['#6b1f26', '#e9cf8f', 17.5, 4.4],
    ['#1f4a3a', '#ecd9a0', 15.0, 3.6],
    ['#1d3552', '#e8d6a2', 16.5, 4.0],
    ['#8a5a1c', '#fbeec6', 14.5, 3.4],
    ['#3d2b22', '#e6c98a', 17.0, 4.8],
    ['#2f4f57', '#f0e2b4', 15.5, 3.8],
    ['#5a2230', '#f1dca0', 16.0, 3.5],
    ['#35502b', '#ecdc9f', 14.0, 4.2],
    ['#7b3a1d', '#f6e3ae', 17.0, 3.6],
    ['#26364a', '#e9d39a', 15.5, 4.6],
];
$shelves = array_chunk(array_keys($rooms), 4);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Library Shelf · optcg.ch prototype</title>
<link rel="stylesheet" href="style.css">
<script>document.documentElement.classList.add('js');</script>
<script src="app.js" defer></script>
</head>
<body>
<a class="skip" href="#case">Skip to the shelves</a>

<header class="head">
  <p class="eyebrow">optcg.ch · One Piece Card Game</p>
  <h1>The Library</h1>
  <p class="lede"><?= count($rooms) ?> volumes, <?= $total ?> references. Take a book from the shelf and open it.</p>
</header>

<main id="case" class="case">
<?php foreach ($shelves as $row): ?>
  <div class="shelf">
    <ul class="books">
<?php foreach ($row as $i):
    $room = $rooms[$i];
    [$cloth, $gilt, $height, $width] = $looks[$i % count($looks)];
    ?>
      <li>
        <a class="spine" href="#<?= h($room['id']) ?>" data-book="<?= h($room['id']) ?>" style="--cloth: <?= $cloth ?>; --gilt: <?= $gilt ?>; --h: <?= $height ?>rem; --w: <?= $width ?>rem">
          <span class="band" aria-hidden="true"></span>
          <span class="s-title"><?= h($room['name']) ?></span>
          <span class="s-who"><?= h($room['character']) ?></span>
          <span class="band" aria-hidden="true"></span>
          <span class="s-no" aria-hidden="true"><?= $i + 1 ?></span>
          <span class="visually-hidden">, <?= h($room['topic']) ?>, <?= count($room['links']) ?> links</span>
        </a>
      </li>
<?php endforeach; ?>
    </ul>
    <div class="plank" aria-hidden="true"></div>
  </div>
<?php endforeach; ?>
</main>

<footer class="foot">Prototype 7 of 7 · fan-made, not affiliated with Bandai or Eiichiro Oda.</footer>

<?php foreach ($rooms as $i => $room):
    [$cloth, $gilt] = $looks[$i % count($looks)];
    ?>
<dialog class="reading" id="<?= h($room['id']) ?>" aria-labelledby="t-<?= h($room['id']) ?>" style="--cloth: <?= $cloth ?>; --gilt: <?= $gilt ?>">
  <div class="book3d">
    <div class="right page">
      <header class="page-head">
        <p class="kicker"><?= h($room['topic']) ?> · <?= h($room['character']) ?></p>
        <h2 id="t-<?= h($room['id']) ?>"><?= h($room['name']) ?></h2>
        <p class="blurb"><?= h($room['description']) ?></p>
      </header>
<?php if ($room['links'] === []): ?>
      <p class="empty">The pages of this volume are still blank.</p>
<?php else: ?>
      <ol class="toc">
<?php foreach ($room['links'] as $n => $link):
    $src = $icon($link);
    $host = preg_replace('/^www\./', '', (string) parse_url((string) $link['url'], PHP_URL_HOST));
    ?>
        <li><a href="<?= h($link['url']) ?>" rel="noopener">
          <?php if ($src !== null): ?><img src="<?= h($src) ?>" alt="" width="36" height="36" loading="lazy"><?php else: ?><span class="fb" aria-hidden="true"><?= h(mb_strtoupper(mb_substr((string) $link['title'], 0, 1))) ?></span><?php endif; ?>
          <span class="entry">
            <span class="row"><span class="ttl"><?= h($link['title']) ?></span><span class="lead" aria-hidden="true"></span><span class="pg"><?= $n + 1 ?></span></span>
            <span class="dsc"><?= h($link['description']) ?></span>
            <span class="hst"><?= h($host) ?></span>
          </span>
        </a></li>
<?php endforeach; ?>
      </ol>
<?php endif; ?>
    </div>
    <div class="cover">
      <div class="cover-front">
        <span class="frame" aria-hidden="true"></span>
        <span class="c-kicker"><?= h($room['topic']) ?></span>
        <span class="c-title"><?= h($room['name']) ?></span>
        <span class="c-rule" aria-hidden="true"></span>
        <span class="c-who"><?= h($room['character']) ?></span>
      </div>
      <div class="cover-back page left" aria-hidden="true">
        <p class="kicker"><?= h($room['topic']) ?></p>
        <p class="l-title"><?= h($room['name']) ?></p>
        <p class="l-who">The deck of <?= h($room['character']) ?></p>
        <p class="l-blurb"><?= h($room['description']) ?></p>
        <p class="l-count"><?= count($room['links']) ?> <?= count($room['links']) === 1 ? 'reference' : 'references' ?></p>
      </div>
    </div>
  </div>
  <a class="close" href="#case" data-close>Close book <span aria-hidden="true">×</span></a>
</dialog>
<?php endforeach; ?>
</body>
</html>
