#!/usr/bin/env php
<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Optcg\Data\DataPaths;
use Optcg\Data\Loader;
use Optcg\LinkCheck\LinkChecker;
use Optcg\LinkCheck\ReportFormatter;
use Optcg\LinkCheck\StreamHttpProbe;

/**
 * Checks every link in data/links/*.yaml (HEAD, falling back to GET) and
 * reports dead ones. Exit code 0 = all reachable, 1 = dead links found,
 * 2 = usage/data error. See docs/plan.md sections 2 and 6, and the GitHub
 * Action .github/workflows/link-check.yml that runs this weekly.
 *
 * Options:
 *   --timeout=SECONDS   per-request timeout (default: 10)
 *   --retries=N         extra attempts per method after the first (default: 2)
 *   --report=PATH       also write the Markdown report to this file
 */

/**
 * @param list<string> $argv
 * @return array<string, string>
 */
function parseOptions(array $argv): array
{
    $options = [];
    foreach ($argv as $arg) {
        if (preg_match('/^--([a-z]+)=(.*)$/', $arg, $matches) === 1) {
            $options[$matches[1]] = $matches[2];
        }
    }

    return $options;
}

$options = parseOptions(array_slice($argv, 1));
$timeout = isset($options['timeout']) ? (int) $options['timeout'] : 10;
$retries = isset($options['retries']) ? (int) $options['retries'] : 2;

$rootDir = dirname(__DIR__);
$loader = new Loader(new DataPaths($rootDir));

$links = [];
foreach ($loader->discoverLinkFileRoomIds() as $roomId) {
    foreach ($loader->loadLinks($roomId) as $link) {
        $link['room'] = $roomId;
        $links[] = $link;
    }
}

if ($links === []) {
    fwrite(STDERR, "No links found under data/links/*.yaml.\n");
    exit(2);
}

$checker = new LinkChecker(new StreamHttpProbe(timeoutSeconds: $timeout), retries: $retries, retryDelayMicroseconds: 300_000);
$results = $checker->checkAll($links);

$formatter = new ReportFormatter();
echo $formatter->summary($results) . "\n";

if (isset($options['report'])) {
    $checkedAtUtc = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->format('Y-m-d H:i') . ' UTC';
    file_put_contents($options['report'], $formatter->markdownReport($results, $checkedAtUtc));
}

exit($formatter->deadLinks($results) === [] ? 0 : 1);
