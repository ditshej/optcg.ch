<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Raw result of fetching a URL: the response body plus enough metadata to
 * resolve further relative links (see UrlResolver) against the URL the
 * server actually served the content from, not the one requested.
 */
final class FetchedResponse
{
    public function __construct(
        public readonly string $body,
        public readonly string $effectiveUrl,
        public readonly ?string $contentType = null,
    ) {
    }
}
