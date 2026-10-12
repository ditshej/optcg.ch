<?php

declare(strict_types=1);

/**
 * The full link list of one room (plan.md section 9): reachable without
 * JavaScript as a plain section further down the page. The dialog overlay
 * on top of this markup is built in a later task (DIT-14).
 *
 * @var array<string, mixed> $r
 * @var \Optcg\View\IconResolver $icons
 */

use Optcg\View\Html;
use Optcg\View\TileVisual;

$room = $r['room'];
$color = $r['color'];
$links = $r['links'];
$roomId = (string) $room['id'];
$headingId = $roomId . '-heading';
?>
<section id="<?= Html::e($roomId) ?>" class="room-section" style="--room-color: <?= Html::e($color) ?>" aria-labelledby="<?= Html::e($headingId) ?>">
<div class="room-section-inner">
<a class="back-link" href="#overview">&larr; Back to the chart</a>
<h2 id="<?= Html::e($headingId) ?>"><?= Html::e((string) $room['name']) ?> <span class="room-character">· <?= Html::e((string) $room['character']) ?></span></h2>
<p class="room-description"><?= Html::e((string) $room['description']) ?></p>
<?php if ($links === []): ?>
<p class="room-empty">No links here yet.</p>
<?php else: ?>
<ul class="link-list">
<?php foreach ($links as $link): ?>
<li class="link-tile<?= !empty($link['is_add_shop']) ? ' link-tile-add' : '' ?>">
<a href="<?= Html::e((string) $link['url']) ?>" target="_blank" rel="noopener">
<span class="link-icon"><?= TileVisual::render($link, $color, $icons) ?></span>
<span class="link-title">
<?= Html::e((string) $link['title']) ?>
<?php if (($link['language'] ?? 'en') !== 'en'): ?>
<span class="lang-badge"><?= Html::e(mb_strtoupper((string) $link['language'])) ?></span>
<?php endif; ?>
</span>
</a>
</li>
<?php endforeach; ?>
</ul>
<?php endif; ?>
</div>
</section>
