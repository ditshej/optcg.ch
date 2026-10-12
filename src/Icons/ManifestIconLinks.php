<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Reads the "icons" array of a web app manifest (docs/plan.md section 5,
 * Icons: the second priority tier after apple-touch-icon).
 */
final class ManifestIconLinks
{
    /**
     * @return list<string> absolute icon URLs, in manifest order
     */
    public static function iconUrls(string $manifestJson, string $manifestUrl): array
    {
        try {
            $data = json_decode($manifestJson, associative: true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        $icons = is_array($data) ? ($data['icons'] ?? null) : null;
        if (!is_array($icons)) {
            return [];
        }

        $urls = [];
        foreach ($icons as $icon) {
            if (!is_array($icon) || !self::hasUsablePurpose($icon)) {
                continue;
            }

            $src = $icon['src'] ?? null;
            if (is_string($src) && $src !== '') {
                $urls[] = UrlResolver::resolve($manifestUrl, $src);
            }
        }

        return $urls;
    }

    /**
     * An icon is usable as a regular tile image only if its "purpose" is
     * missing (spec default "any") or explicitly lists "any". "monochrome"
     * and bare "maskable" icons are meant to be recoloured or clipped by the
     * consumer, not drawn as-is, and must be skipped.
     *
     * @param array<mixed> $icon
     */
    private static function hasUsablePurpose(array $icon): bool
    {
        $purpose = $icon['purpose'] ?? null;
        if (!is_string($purpose) || trim($purpose) === '') {
            return true;
        }

        return in_array('any', preg_split('/\s+/', trim($purpose)), strict: true);
    }
}
