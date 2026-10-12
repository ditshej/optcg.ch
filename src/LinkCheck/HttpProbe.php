<?php

declare(strict_types=1);

namespace Optcg\LinkCheck;

/**
 * Sends one HTTP request to a URL. LinkChecker calls this for HEAD first,
 * then GET as a fallback, and handles retries itself (see LinkChecker).
 */
interface HttpProbe
{
    public function probe(string $url, string $method): ProbeResult;
}
