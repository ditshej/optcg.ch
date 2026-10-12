<?php

declare(strict_types=1);

/**
 * @var list<array<string, mixed>> $roomViews
 * @var \Optcg\View\IconResolver $icons
 */

use Optcg\View\Html;

?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>optcg.ch — a chart of the One Piece Card Game seas</title>
<meta name="description" content="Links about the One Piece Card Game, grouped by the rooms of the Thousand Sunny.">
<link rel="icon" type="image/svg+xml" href="favicon.svg">
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<a class="skip-link" href="#overview">Skip to the overview</a>
<header class="site-header">
<a class="logo" href="#overview" aria-label="optcg.ch, back to the overview">
<?php readfile(__DIR__ . '/../public/assets/compass-rose.svg'); ?>
<span class="logo-text">optcg.ch</span>
</a>
<p class="site-tagline">A chart of the One Piece Card Game seas</p>
</header>
<main id="overview" class="overview">
<h1 class="visually-hidden">Rooms of the Thousand Sunny</h1>
<div class="folder-grid">
<?php foreach ($roomViews as $r): ?>
<?php require __DIR__ . '/folder.php'; ?>
<?php endforeach; ?>
</div>
</main>
<div class="room-sections">
<?php foreach ($roomViews as $r): ?>
<?php require __DIR__ . '/room-section.php'; ?>
<?php endforeach; ?>
</div>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
