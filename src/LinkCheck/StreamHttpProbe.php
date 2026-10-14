<?php

declare(strict_types=1);

namespace Optcg\LinkCheck;

/**
 * Real HttpProbe implementation using PHP's http:// stream wrapper
 * (no extra extension required beyond what Composer/CI already installs).
 * Sends a custom User-Agent naming the repo, per docs/plan.md section 6.
 */
final class StreamHttpProbe implements HttpProbe
{
    public function __construct(
        private readonly int $timeoutSeconds = 10,
        private readonly string $userAgent = 'optcg.ch-link-check/1.0 (+https://github.com/ditshej/optcg.ch)',
    ) {
    }

    public function probe(string $url, string $method): ProbeResult
    {
        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'timeout' => $this->timeoutSeconds,
                'header' => "User-Agent: {$this->userAgent}\r\n",
                'follow_location' => 1,
                'max_redirects' => 5,
                'ignore_errors' => true,
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);

        $errorMessage = null;
        set_error_handler(static function (int $errno, string $errstr) use (&$errorMessage): bool {
            $errorMessage = $errstr;

            return true;
        });

        try {
            $body = file_get_contents($url, false, $context);
        } finally {
            restore_error_handler();
        }

        if ($body === false) {
            return new ProbeResult(ok: false, statusCode: null, error: $errorMessage ?? 'Connection failed.');
        }

        $statusCode = $this->statusCodeFromHeaders($http_response_header ?? []);
        if ($statusCode === null) {
            return new ProbeResult(ok: false, statusCode: null, error: 'No HTTP status line in the response.');
        }

        return new ProbeResult(ok: $statusCode >= 200 && $statusCode < 400, statusCode: $statusCode, error: null);
    }

    /**
     * @param list<string> $headers raw header lines, as in $http_response_header
     */
    private function statusCodeFromHeaders(array $headers): ?int
    {
        // With redirects followed, $http_response_header holds one status line per
        // hop, so the last "HTTP/..." line is the final response's status.
        $statusCode = null;
        foreach ($headers as $header) {
            if (preg_match('#^HTTP/\S+\s+(\d{3})#', $header, $matches) === 1) {
                $statusCode = (int) $matches[1];
            }
        }

        return $statusCode;
    }
}
