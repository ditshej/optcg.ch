<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Reads the icon-related <link> tags from a page's <head>, in the priority
 * order from docs/plan.md section 5 (Icons): apple-touch-icon, then the web
 * app manifest, then a plain <link rel="icon">. /favicon.ico is not a tag
 * and is handled separately by IconSelector.
 */
final class HtmlIconLinks
{
    /**
     * @return list<string> absolute apple-touch-icon URLs, in document order
     */
    public static function appleTouchIcons(string $html, string $pageUrl): array
    {
        return self::hrefsForRel($html, $pageUrl, ['apple-touch-icon', 'apple-touch-icon-precomposed']);
    }

    /**
     * @return list<string> absolute <link rel="icon"> / "shortcut icon" URLs, in document order
     */
    public static function iconLinks(string $html, string $pageUrl): array
    {
        return self::hrefsForRel($html, $pageUrl, ['icon', 'shortcut icon']);
    }

    public static function manifestUrl(string $html, string $pageUrl): ?string
    {
        $hrefs = self::hrefsForRel($html, $pageUrl, ['manifest']);

        return $hrefs[0] ?? null;
    }

    /**
     * @param list<string> $relValues matched case-insensitively against each link's rel attribute
     * @return list<string>
     */
    private static function hrefsForRel(string $html, string $pageUrl, array $relValues): array
    {
        $document = self::parseHtml($html);
        if ($document === null) {
            return [];
        }

        $hrefs = [];

        foreach ($document->getElementsByTagName('link') as $link) {
            $rel = strtolower(trim($link->getAttribute('rel')));
            $href = trim($link->getAttribute('href'));

            if ($href === '') {
                continue;
            }

            foreach ($relValues as $relValue) {
                if ($rel === $relValue) {
                    $hrefs[] = UrlResolver::resolve($pageUrl, $href);

                    break;
                }
            }
        }

        return $hrefs;
    }

    private static function parseHtml(string $html): ?\DOMDocument
    {
        $document = new \DOMDocument();

        $previous = libxml_use_internal_errors(true);
        $loaded = $document->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return $loaded ? $document : null;
    }
}
