<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Real HttpFetcher for bin/fetch-icons.php, built on ext-curl. Never used in
 * tests (see tests/Icons/FakeHttpFetcher.php), so the icon selection logic
 * stays testable without network access.
 */
final class CurlHttpFetcher implements HttpFetcher
{
    private const USER_AGENT = 'optcg.ch-icon-fetcher/1.0 (+https://github.com/ditshej/optcg.ch)';
    private const TIMEOUT_SECONDS = 10;
    private const MAX_REDIRECTS = 5;

    /** Stop downloading once a response exceeds this size; icons/pages this large are not useful anyway. */
    private const MAX_BYTES = 5 * 1024 * 1024;

    public function fetch(string $url): ?FetchedResponse
    {
        $handle = curl_init($url);
        if ($handle === false) {
            return null;
        }

        $body = '';
        curl_setopt_array($handle, [
            CURLOPT_HTTPGET => true,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_WRITEFUNCTION => static function ($ch, string $chunk) use (&$body): int {
                $body .= $chunk;

                return strlen($chunk) > 0 && strlen($body) > self::MAX_BYTES ? 0 : strlen($chunk);
            },
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => self::MAX_REDIRECTS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_USERAGENT => self::USER_AGENT,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
            CURLOPT_REDIR_PROTOCOLS => CURLPROTO_HTTP | CURLPROTO_HTTPS,
        ]);

        curl_exec($handle);
        $statusCode = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        $effectiveUrl = (string) (curl_getinfo($handle, CURLINFO_EFFECTIVE_URL) ?: $url);
        $contentType = curl_getinfo($handle, CURLINFO_CONTENT_TYPE);
        $errored = curl_errno($handle) !== 0;
        curl_close($handle);

        if ($errored || $statusCode < 200 || $statusCode >= 300 || $body === '') {
            return null;
        }

        return new FetchedResponse($body, $effectiveUrl, is_string($contentType) ? $contentType : null);
    }
}
