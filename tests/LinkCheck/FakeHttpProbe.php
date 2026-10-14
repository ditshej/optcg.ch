<?php

declare(strict_types=1);

namespace Optcg\Tests\LinkCheck;

use Optcg\LinkCheck\HttpProbe;
use Optcg\LinkCheck\ProbeResult;

/**
 * Deterministic HttpProbe stand-in: each probe() call pops the next
 * scripted ProbeResult for its (method, url) pair, so tests can simulate
 * network behaviour (success, failure, flaky retries) without any network.
 */
final class FakeHttpProbe implements HttpProbe
{
    /** @var array<string, list<ProbeResult>> queued results per "METHOD url" key */
    private array $queued = [];

    /** @var list<array{method: string, url: string}> */
    public array $calls = [];

    public function queue(string $method, string $url, ProbeResult $result): void
    {
        $this->queued["{$method} {$url}"][] = $result;
    }

    public function probe(string $url, string $method): ProbeResult
    {
        $this->calls[] = ['method' => $method, 'url' => $url];

        $key = "{$method} {$url}";
        if (!isset($this->queued[$key]) || $this->queued[$key] === []) {
            throw new \RuntimeException("FakeHttpProbe: no queued result for {$key}.");
        }

        return array_shift($this->queued[$key]);
    }
}
