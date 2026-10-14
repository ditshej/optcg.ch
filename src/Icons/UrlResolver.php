<?php

declare(strict_types=1);

namespace Optcg\Icons;

/**
 * Resolves a possibly-relative URL (as found in an href/src attribute)
 * against the base URL of the page or manifest it came from.
 */
final class UrlResolver
{
    public static function resolve(string $baseUrl, string $reference): string
    {
        $reference = trim($reference);

        if ($reference === '') {
            return $baseUrl;
        }

        if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.-]*://#', $reference) === 1) {
            return $reference;
        }

        $base = parse_url($baseUrl);
        if ($base === false || !isset($base['scheme'], $base['host'])) {
            return $reference;
        }

        $origin = $base['scheme'] . '://' . $base['host'] . (isset($base['port']) ? ':' . $base['port'] : '');

        if (str_starts_with($reference, '//')) {
            return $base['scheme'] . ':' . $reference;
        }

        if (str_starts_with($reference, '/')) {
            return $origin . $reference;
        }

        $basePath = $base['path'] ?? '/';
        $baseDir = str_ends_with($basePath, '/') ? $basePath : (dirname($basePath) . '/');

        return $origin . self::normalizePath($baseDir . $reference);
    }

    /** Resolves "." and ".." segments the way a browser would. */
    private static function normalizePath(string $path): string
    {
        $segments = explode('/', $path);
        $resolved = [];

        foreach ($segments as $segment) {
            if ($segment === '.' || $segment === '') {
                continue;
            }

            if ($segment === '..') {
                array_pop($resolved);

                continue;
            }

            $resolved[] = $segment;
        }

        return '/' . implode('/', $resolved);
    }
}
