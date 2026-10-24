<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Picks the public channel image of a YouTube channel (the og:image of the
 * channel page) instead of the YouTube logo that IconSelector would find.
 * Only channel URLs (youtube.com/@handle and youtube.com/channel/ID, also
 * www. and m.) count; video URLs and every other host are not handled.
 * Returns null whenever there is no usable channel image, so the caller can
 * fall back to IconSelector.
 */
final class YouTubeChannelIcon
{
    private const DEFAULT_MIN_SIZE_PX = 64;

    public function __construct(
        private readonly HttpFetcher $fetcher,
        private readonly int $minSizePx = self::DEFAULT_MIN_SIZE_PX,
    ) {
    }

    public static function isChannelUrl(string $url): bool
    {
        $parts = parse_url($url);
        if (!is_array($parts) || !isset($parts['host'])) {
            return false;
        }

        if (!in_array(strtolower($parts['host']), ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            return false;
        }

        return preg_match('~^/(@[^/]+|channel/[^/]+)/?~', $parts['path'] ?? '') === 1;
    }

    public function select(string $pageUrl): ?SelectedIcon
    {
        if (!self::isChannelUrl($pageUrl)) {
            return null;
        }

        $page = $this->fetcher->fetch($pageUrl);
        if ($page === null) {
            return null;
        }

        $imageUrl = self::ogImageUrl($page->body, $page->effectiveUrl);
        if ($imageUrl === null) {
            return null;
        }

        $response = $this->fetcher->fetch($imageUrl);
        $decoded = $response !== null ? ImageInspector::decode($response->body) : null;
        if ($decoded === null || $decoded->width !== $decoded->height || $decoded->width < $this->minSizePx) {
            return null;
        }

        return new SelectedIcon($decoded->image, $decoded->width, $decoded->height, $imageUrl, 'youtube-channel');
    }

    /**
     * @return string|null absolute URL of the og:image meta tag, if any
     */
    public static function ogImageUrl(string $html, string $pageUrl): ?string
    {
        $document = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        if (!$loaded) {
            return null;
        }

        foreach ($document->getElementsByTagName('meta') as $meta) {
            $property = strtolower(trim($meta->getAttribute('property')));
            $content = trim($meta->getAttribute('content'));

            if ($property === 'og:image' && $content !== '') {
                return UrlResolver::resolve($pageUrl, $content);
            }
        }

        return null;
    }
}
