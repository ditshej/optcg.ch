<?php

declare(strict_types=1);

/**
 * One folder tile in the overview grid. Included in a loop from layout.php
 * with $r = ['room' => ..., 'links' => ..., 'color' => ..., 'preview' => ...].
 *
 * @var array<string, mixed> $r
 * @var \Optcg\View\IconResolver $icons
 */

use Optcg\View\Html;
use Optcg\View\TileVisual;

$room = $r['room'];
$color = $r['color'];
$preview = $r['preview'];
?>
<a class="folder" href="#<?= Html::e((string) $room['id']) ?>"
   aria-label="Open the <?= Html::e((string) $room['name']) ?> folder — <?= Html::e((string) $room['topic']) ?>">
<span class="folder-icon" style="--room-color: <?= Html::e($color) ?>" aria-hidden="true">
<?php if ($preview['small'] === null): ?>
<?php foreach ($preview['large'] as $link): ?>
<span class="folder-slot<?= $link === null ? ' folder-slot-empty' : '' ?>"><?= TileVisual::render($link, $color, $icons) ?></span>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($preview['large'] as $link): ?>
<span class="folder-slot"><?= TileVisual::render($link, $color, $icons) ?></span>
<?php endforeach; ?>
<span class="folder-slot folder-slot-group">
<?php foreach ($preview['small'] as $link): ?>
<span class="folder-slot-mini<?= $link === null ? ' folder-slot-mini-empty' : '' ?>"><?= TileVisual::render($link, $color, $icons) ?></span>
<?php endforeach; ?>
</span>
<?php endif; ?>
</span>
<span class="folder-topic"><?= Html::e((string) $room['topic']) ?></span>
</a>
