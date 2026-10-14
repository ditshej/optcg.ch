<?php

declare(strict_types=1);

namespace Optcg\LinkCheck;

/**
 * Checks each link with HEAD first, falling back to GET when HEAD does not
 * succeed (some servers block or misreport HEAD), with a few retries per
 * method to absorb transient network blips (docs/plan.md sections 2 and 6).
 */
final class LinkChecker
{
    public function __construct(
        private readonly HttpProbe $prober,
        private readonly int $retries = 2,
        private readonly int $retryDelayMicroseconds = 0,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $links each with at least
     *        'room', 'id', 'title' and 'url' keys (see bin/check-links.php)
     * @return list<LinkCheckResult>
     */
    public function checkAll(array $links): array
    {
        return array_map($this->checkLink(...), array_values($links));
    }

    /**
     * @param array<string, mixed> $link
     */
    public function checkLink(array $link): LinkCheckResult
    {
        $url = (string) $link['url'];

        $method = 'HEAD';
        $result = $this->attempt($url, $method);

        if (!$result->ok) {
            $method = 'GET';
            $result = $this->attempt($url, $method);
        }

        return new LinkCheckResult(
            roomId: (string) $link['room'],
            linkId: (string) $link['id'],
            title: (string) $link['title'],
            url: $url,
            ok: $result->ok,
            method: $method,
            statusCode: $result->statusCode,
            error: $result->error,
        );
    }

    private function attempt(string $url, string $method): ProbeResult
    {
        $result = $this->prober->probe($url, $method);

        for ($attempt = 1; $attempt <= $this->retries && !$result->ok; $attempt++) {
            if ($this->retryDelayMicroseconds > 0) {
                usleep($this->retryDelayMicroseconds);
            }

            $result = $this->prober->probe($url, $method);
        }

        return $result;
    }
}
