<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Fetches a URL for the icon selection logic in IconSelector. Kept as an
 * interface so tests can run the selection logic against canned HTML and
 * image fixtures without any network access (see DIT-12 acceptance criteria).
 */
interface HttpFetcher
{
    /**
     * Returns null when the URL could not be fetched (network error, timeout,
     * non-2xx status). IconSelector treats that as "no candidate here" and
     * moves on, it never throws for an unreachable target site.
     */
    public function fetch(string $url): ?FetchedResponse;
}
