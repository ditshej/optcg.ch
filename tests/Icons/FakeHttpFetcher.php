<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

use Optcg\Icons\FetchedResponse;
use Optcg\Icons\HttpFetcher;

/**
 * Test double for HttpFetcher: serves canned responses from an in-memory
 * map instead of the network, so IconSelector tests stay fast and
 * deterministic (DIT-12 acceptance criteria: no network in tests).
 */
final class FakeHttpFetcher implements HttpFetcher
{
    /** @var array<string, FetchedResponse> */
    private array $responses = [];

    public function withHtml(string $url, string $html): self
    {
        $this->responses[$url] = new FetchedResponse($html, $url, 'text/html');

        return $this;
    }

    public function withJson(string $url, string $json): self
    {
        $this->responses[$url] = new FetchedResponse($json, $url, 'application/json');

        return $this;
    }

    public function withImage(string $url, string $imageBytes): self
    {
        $this->responses[$url] = new FetchedResponse($imageBytes, $url, 'image/png');

        return $this;
    }

    public function withImageFromFixture(string $url, int $squareSize): self
    {
        return $this->withImage($url, PngFixture::square($squareSize));
    }

    public function fetch(string $url): ?FetchedResponse
    {
        return $this->responses[$url] ?? null;
    }
}
