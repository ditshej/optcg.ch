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
            $src = is_array($icon) ? ($icon['src'] ?? null) : null;
            if (is_string($src) && $src !== '') {
                $urls[] = UrlResolver::resolve($manifestUrl, $src);
            }
        }

        return $urls;
    }
}
