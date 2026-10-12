<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

use Optcg\Icons\HtmlIconLinks;
use PHPUnit\Framework\TestCase;

final class HtmlIconLinksTest extends TestCase
{
    public function testFindsAppleTouchIconsInDocumentOrder(): void
    {
        $html = <<<'HTML'
            <html><head>
                <link rel="apple-touch-icon" sizes="57x57" href="/apple-57.png">
                <link rel="apple-touch-icon" sizes="180x180" href="/apple-180.png">
                <link rel="apple-touch-icon-precomposed" href="/apple-precomposed.png">
            </head><body></body></html>
            HTML;

        self::assertSame(
            [
                'https://example.com/apple-57.png',
                'https://example.com/apple-180.png',
                'https://example.com/apple-precomposed.png',
            ],
            HtmlIconLinks::appleTouchIcons($html, 'https://example.com/'),
        );
    }

    public function testFindsIconAndShortcutIconLinks(): void
    {
        $html = <<<'HTML'
            <html><head>
                <link rel="shortcut icon" href="/old-favicon.ico">
                <link rel="icon" type="image/png" href="/icon.png">
            </head><body></body></html>
            HTML;

        self::assertSame(
            ['https://example.com/old-favicon.ico', 'https://example.com/icon.png'],
            HtmlIconLinks::iconLinks($html, 'https://example.com/'),
        );
    }

    public function testFindsManifestUrl(): void
    {
        $html = '<html><head><link rel="manifest" href="/manifest.webmanifest"></head></html>';

        self::assertSame(
            'https://example.com/manifest.webmanifest',
            HtmlIconLinks::manifestUrl($html, 'https://example.com/'),
        );
    }

    public function testMissingTagsReturnEmptyResults(): void
    {
        $html = '<html><head><title>No icons here</title></head><body></body></html>';

        self::assertSame([], HtmlIconLinks::appleTouchIcons($html, 'https://example.com/'));
        self::assertSame([], HtmlIconLinks::iconLinks($html, 'https://example.com/'));
        self::assertNull(HtmlIconLinks::manifestUrl($html, 'https://example.com/'));
    }

    public function testIgnoresUnrelatedLinkRelValues(): void
    {
        $html = <<<'HTML'
            <html><head>
                <link rel="stylesheet" href="/style.css">
                <link rel="canonical" href="https://example.com/">
            </head></html>
            HTML;

        self::assertSame([], HtmlIconLinks::appleTouchIcons($html, 'https://example.com/'));
        self::assertSame([], HtmlIconLinks::iconLinks($html, 'https://example.com/'));
    }
}
