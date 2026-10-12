<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Picks the best icon for one target page, following docs/plan.md section 5
 * (Icons): apple-touch-icon, then the web app manifest, then
 * <link rel="icon">, last /favicon.ico. Within the first tier that has at
 * least one usable candidate, the largest square image wins; a candidate
 * smaller than minSizePx does not count as usable (the fallback tile is
 * used instead, per the plan's "< 64 px -> no icon" rule).
 */
final class IconSelector
{
    private const DEFAULT_MIN_SIZE_PX = 64;

    public function __construct(
        private readonly HttpFetcher $fetcher,
        private readonly int $minSizePx = self::DEFAULT_MIN_SIZE_PX,
    ) {
    }

    public function selectBestIcon(string $pageUrl): ?SelectedIcon
    {
        $tiers = $this->candidateUrlsByTier($pageUrl);

        foreach ($tiers as $tier => $urls) {
            $winner = $this->bestQualifyingCandidate($urls);

            if ($winner !== null) {
                return new SelectedIcon($winner->image, $winner->width, $winner->height, $winner->sourceUrl, $tier);
            }
        }

        return null;
    }

    /**
     * @return array<string, list<string>> candidate URLs keyed by tier name, in priority order
     */
    private function candidateUrlsByTier(string $pageUrl): array
    {
        $page = $this->fetcher->fetch($pageUrl);
        $effectivePageUrl = $page?->effectiveUrl ?? $pageUrl;
        $html = $page?->body ?? '';

        $manifestUrl = $page !== null ? HtmlIconLinks::manifestUrl($html, $effectivePageUrl) : null;

        return [
            'apple-touch-icon' => $page !== null ? HtmlIconLinks::appleTouchIcons($html, $effectivePageUrl) : [],
            'manifest' => $manifestUrl !== null ? $this->manifestIconUrls($manifestUrl) : [],
            'icon' => $page !== null ? HtmlIconLinks::iconLinks($html, $effectivePageUrl) : [],
            'favicon.ico' => $this->faviconFallbackUrl($effectivePageUrl),
        ];
    }

    /**
     * @return list<string>
     */
    private function manifestIconUrls(string $manifestUrl): array
    {
        $manifest = $this->fetcher->fetch($manifestUrl);

        return $manifest !== null ? ManifestIconLinks::iconUrls($manifest->body, $manifest->effectiveUrl) : [];
    }

    /**
     * @return list<string>
     */
    private function faviconFallbackUrl(string $pageUrl): array
    {
        $origin = parse_url($pageUrl);
        if (!isset($origin['scheme'], $origin['host'])) {
            return [];
        }

        return [$origin['scheme'] . '://' . $origin['host'] . '/favicon.ico'];
    }

    /**
     * Fetches and decodes every candidate, keeps the square ones at or above
     * minSizePx, and returns the largest.
     *
     * @param list<string> $urls
     */
    private function bestQualifyingCandidate(array $urls): ?DecodedCandidate
    {
        $best = null;

        foreach ($urls as $url) {
            $response = $this->fetcher->fetch($url);
            if ($response === null) {
                continue;
            }

            $decoded = ImageInspector::decode($response->body);
            if ($decoded === null) {
                continue;
            }

            if ($decoded->width !== $decoded->height || $decoded->width < $this->minSizePx) {
                continue;
            }

            if ($best === null || $decoded->width > $best->width) {
                $best = new DecodedCandidate($decoded->image, $decoded->width, $decoded->height, $url);
            }
        }

        return $best;
    }
}
