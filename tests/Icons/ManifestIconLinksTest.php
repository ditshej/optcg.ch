<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

use Optcg\Icons\ManifestIconLinks;
use PHPUnit\Framework\TestCase;

final class ManifestIconLinksTest extends TestCase
{
    public function testExtractsIconUrlsInManifestOrder(): void
    {
        $manifest = json_encode([
            'name' => 'Example',
            'icons' => [
                ['src' => 'icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => '/icons/512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ],
        ]);

        self::assertSame(
            ['https://example.com/manifest/icons/192.png', 'https://example.com/icons/512.png'],
            ManifestIconLinks::iconUrls((string) $manifest, 'https://example.com/manifest/app.webmanifest'),
        );
    }

    public function testSkipsMonochromeAndMaskableOnlyIcons(): void
    {
        $manifest = json_encode([
            'name' => 'Example',
            'icons' => [
                ['src' => 'icons/192.png', 'sizes' => '192x192', 'type' => 'image/png'],
                ['src' => 'icons/mono-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'monochrome'],
                ['src' => 'icons/maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
                ['src' => 'icons/any-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'any maskable'],
            ],
        ]);

        self::assertSame(
            [
                'https://example.com/manifest/icons/192.png',
                'https://example.com/manifest/icons/any-maskable-512.png',
            ],
            ManifestIconLinks::iconUrls((string) $manifest, 'https://example.com/manifest/app.webmanifest'),
        );
    }

    public function testMissingIconsArrayReturnsEmptyList(): void
    {
        self::assertSame([], ManifestIconLinks::iconUrls('{"name":"Example"}', 'https://example.com/manifest.json'));
    }

    public function testInvalidJsonReturnsEmptyList(): void
    {
        self::assertSame([], ManifestIconLinks::iconUrls('not json', 'https://example.com/manifest.json'));
    }
}
