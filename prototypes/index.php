<?php

declare(strict_types=1);

// Lists every prototypes/NN-slug/ folder. Name and description come from the folder's README.md:
//   name        first "# " heading (a leading "NN ·" number is dropped)
//   description the "**Idea:**" paragraph, else the first plain paragraph
//   technique   the "**Animation technique:**" paragraph, if present
// Adding a prototype needs no change here: create prototypes/NN-slug/ with an index.php (or index.html) and a README.md.

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Strips Markdown emphasis and code ticks; keeps the text readable. */
function plain(string $text): string
{
    $text = preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text) ?? $text;

    return trim(preg_replace('/[*_`]+/', '', $text) ?? $text);
}

/** @return array{name: string, description: string, technique: string} */
function read_readme(string $file, string $fallbackName): array
{
    $info = ['name' => $fallbackName, 'description' => '', 'technique' => ''];
    if (!is_file($file)) {
        return $info;
    }
    $paragraphs = preg_split('/\R{2,}/', trim((string) file_get_contents($file))) ?: [];
    $plain = '';
    foreach ($paragraphs as $paragraph) {
        $paragraph = trim($paragraph);
        if (preg_match('/^#\s+(.+)$/m', $paragraph, $m) && str_starts_with($paragraph, '#')) {
            $name = trim(preg_replace('/^\d+\s*[·.:\-]\s*/u', '', plain($m[1])) ?? '');
            $info['name'] = $name !== '' ? $name : $fallbackName;
            continue;
        }
        if (preg_match('/^\*\*Idea:?\*\*:?\s*(.+)$/s', $paragraph, $m)) {
            $info['description'] = plain(preg_replace('/\s+/', ' ', $m[1]) ?? '');
        } elseif (preg_match('/^\*\*Animation technique:?\*\*:?\s*(.+)$/s', $paragraph, $m)) {
            $info['technique'] = plain(preg_replace('/\s+/', ' ', $m[1]) ?? '');
        } elseif ($plain === '' && !preg_match('/^(#|[-*]\s|\*\*|```|\|)/', $paragraph)) {
            $plain = plain(preg_replace('/\s+/', ' ', $paragraph) ?? '');
        }
    }
    if ($info['description'] === '') {
        $info['description'] = $plain;
    }

    return $info;
}

/** First sentence(s) up to about $max characters, ending on a full stop. */
function teaser(string $text, int $max = 230): string
{
    if (mb_strlen($text) <= $max) {
        return $text;
    }
    $cut = mb_substr($text, 0, $max);
    $stop = mb_strrpos($cut, '. ');

    if ($stop !== false && $stop > 60) {
        return mb_substr($cut, 0, $stop + 1);
    }
    $space = mb_strrpos($cut, ' ');

    return rtrim($space !== false ? mb_substr($cut, 0, $space) : $cut, ' ,;:') . '…';
}

$items = [];
foreach (glob(__DIR__ . '/[0-9][0-9]-*', GLOB_ONLYDIR) ?: [] as $dir) {
    $folder = basename($dir);
    $number = substr($folder, 0, 2);
    $entry = null;
    foreach (['index.php', 'index.html'] as $candidate) {
        if (is_file($dir . '/' . $candidate)) {
            $entry = $folder . '/';
            break;
        }
    }
    $slug = ucwords(str_replace('-', ' ', substr($folder, 3)));
    $items[] = ['number' => $number, 'folder' => $folder, 'href' => $entry] + read_readme($dir . '/README.md', $slug);
}
usort($items, static fn (array $a, array $b): int => strcmp($a['folder'], $b['folder']));
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Design prototypes · optcg.ch</title>
<style>
  :root { color-scheme: light dark; --bg: #f6f5f1; --fg: #15191d; --mu: #555e66; --line: #d8d6ce; --card: #fff; --accent: #0b5c8a; }
  @media (prefers-color-scheme: dark) { :root { --bg: #111518; --fg: #ecebe6; --mu: #a4adb4; --line: #2a3238; --card: #181d21; --accent: #7cc4ee; } }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--bg); color: var(--fg); font: 400 1rem/1.55 system-ui, -apple-system, "Segoe UI", sans-serif; }
  main { max-width: 60rem; margin: 0 auto; padding: clamp(1.5rem, 5vw, 3.5rem) 1.25rem 4rem; }
  h1 { margin: 0; font-size: clamp(1.75rem, 4vw, 2.5rem); letter-spacing: -.02em; }
  .intro { max-width: 40rem; color: var(--mu); }
  ol { list-style: none; margin: 2rem 0 0; padding: 0; display: grid; gap: .75rem; grid-template-columns: repeat(auto-fill, minmax(min(100%, 26rem), 1fr)); }
  li a, li > div { display: grid; grid-template-columns: 3rem 1fr; gap: 1rem; height: 100%; padding: 1.1rem 1.25rem; color: inherit; text-decoration: none; background: var(--card); border: 1px solid var(--line); border-radius: 6px; }
  li a:hover { border-color: var(--accent); }
  a:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
  .no { font: 600 1.5rem/1.2 ui-monospace, "SF Mono", Menlo, monospace; color: var(--accent); }
  h2 { margin: 0 0 .35rem; font-size: 1.25rem; }
  p { margin: 0; }
  .desc { font-size: .9375rem; color: var(--mu); }
  .tech { margin-top: .6rem; font-size: .8125rem; color: var(--mu); }
  .tech b { color: var(--fg); }
  .missing { opacity: .6; }
  code { font: .875em ui-monospace, Menlo, monospace; }
</style>
</head>
<body>
<main>
  <h1>Design prototypes</h1>
  <p class="intro">Directions for the optcg.ch landing page. Each prototype is self-contained in <code>prototypes/NN-name/</code> and reads the real rooms and links from <code>data/</code>. Start locally with <code>php -S localhost:8000 -t .</code> in the repository root and open <code>/prototypes/</code>.</p>
<?php if ($items === []): ?>
  <p class="intro">No prototype folders found.</p>
<?php else: ?>
  <ol>
<?php foreach ($items as $item): ?>
    <li>
<?php if ($item['href'] !== null): ?>
      <a href="<?= h($item['href']) ?>">
<?php else: ?>
      <div class="missing">
<?php endif; ?>
        <span class="no"><?= h($item['number']) ?></span>
        <span>
          <h2><?= h($item['name']) ?></h2>
          <p class="desc"><?= h(teaser($item['description'])) ?><?= $item['href'] === null ? ' (no index.php or index.html yet)' : '' ?></p>
<?php if ($item['technique'] !== ''): ?>
          <p class="tech"><b>Animation:</b> <?= h(teaser($item['technique'], 150)) ?></p>
<?php endif; ?>
        </span>
<?php if ($item['href'] !== null): ?>
      </a>
<?php else: ?>
      </div>
<?php endif; ?>
    </li>
<?php endforeach; ?>
  </ol>
<?php endif; ?>
</main>
</body>
</html>
