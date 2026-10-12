<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

use Optcg\Icons\IconSelector;
use PHPUnit\Framework\TestCase;

/**
 * Covers the icon selection logic (docs/plan.md section 5) against local
 * HTML/manifest fixtures, without any network access (DIT-12 acceptance
 * criteria): tier priority (apple-touch-icon > manifest > <link rel="icon">
 * > /favicon.ico), "largest square wins" within a tier, and the
 * "< 64 px -> no icon" floor.
 */
final class IconSelectorTest extends TestCase
{
    private const PAGE_URL = 'https://example.com/';
    private const FAVICON_URL = 'https://example.com/favicon.ico';

    public function testPrefersAppleTouchIconOverManifestAndIconLink(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, <<<'HTML'
                <html><head>
                    <link rel="apple-touch-icon" href="/apple.png">
                    <link rel="manifest" href="/manifest.json">
                    <link rel="icon" href="/favicon.png">
                </head></html>
                HTML)
            ->withImageFromFixture('https://example.com/apple.png', 180)
            ->withJson('https://example.com/manifest.json', json_encode(['icons' => [
                ['src' => '/manifest-icon.png'],
            ]]))
            ->withImageFromFixture('https://example.com/manifest-icon.png', 512)
            ->withImageFromFixture('https://example.com/favicon.png', 96);

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('apple-touch-icon', $selected->tier);
        self::assertSame(180, $selected->width);
    }

    public function testLargestSquareWinsWithinTheWinningTier(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, <<<'HTML'
                <html><head>
                    <link rel="apple-touch-icon" sizes="57x57" href="/apple-57.png">
                    <link rel="apple-touch-icon" sizes="180x180" href="/apple-180.png">
                    <link rel="apple-touch-icon" sizes="120x120" href="/apple-120.png">
                </head></html>
                HTML)
            ->withImageFromFixture('https://example.com/apple-57.png', 57)
            ->withImageFromFixture('https://example.com/apple-180.png', 180)
            ->withImageFromFixture('https://example.com/apple-120.png', 120);

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('https://example.com/apple-180.png', $selected->sourceUrl);
        self::assertSame(180, $selected->width);
    }

    public function testFallsThroughToManifestWhenNoAppleTouchIcon(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, <<<'HTML'
                <html><head>
                    <link rel="manifest" href="/manifest.json">
                    <link rel="icon" href="/favicon.png">
                </head></html>
                HTML)
            ->withJson('https://example.com/manifest.json', json_encode(['icons' => [
                ['src' => '/manifest-icon.png'],
            ]]))
            ->withImageFromFixture('https://example.com/manifest-icon.png', 512)
            ->withImageFromFixture('https://example.com/favicon.png', 96);

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('manifest', $selected->tier);
        self::assertSame(512, $selected->width);
    }

    public function testFallsThroughToIconLinkWhenManifestHasNoIcons(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, <<<'HTML'
                <html><head>
                    <link rel="manifest" href="/manifest.json">
                    <link rel="icon" href="/favicon.png">
                </head></html>
                HTML)
            ->withJson('https://example.com/manifest.json', json_encode(['name' => 'Example']))
            ->withImageFromFixture('https://example.com/favicon.png', 96);

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('icon', $selected->tier);
        self::assertSame(96, $selected->width);
    }

    public function testFallsThroughToFaviconIcoWhenNoTagsArePresent(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, '<html><head><title>No icon tags</title></head></html>')
            ->withImage(self::FAVICON_URL, IcoFixture::withPngEntries([64]));

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('favicon.ico', $selected->tier);
        self::assertSame(64, $selected->width);
    }

    public function testTooSmallCandidateDoesNotWinItsTier(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, <<<'HTML'
                <html><head>
                    <link rel="apple-touch-icon" href="/apple-tiny.png">
                    <link rel="icon" href="/favicon.png">
                </head></html>
                HTML)
            ->withImageFromFixture('https://example.com/apple-tiny.png', 32)
            ->withImageFromFixture('https://example.com/favicon.png', 96);

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('icon', $selected->tier, 'a 32px apple-touch-icon must not win over a usable 96px <link rel="icon">');
        self::assertSame(96, $selected->width);
    }

    public function testNonSquareCandidateIsIgnored(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, <<<'HTML'
                <html><head>
                    <link rel="apple-touch-icon" href="/apple-wide.png">
                    <link rel="icon" href="/favicon.png">
                </head></html>
                HTML)
            ->withImage('https://example.com/apple-wide.png', PngFixture::rectangle(180, 90))
            ->withImageFromFixture('https://example.com/favicon.png', 96);

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('icon', $selected->tier);
    }

    public function testReturnsNullWhenNothingQualifiesAnywhere(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, '<html><head><title>No icon tags</title></head></html>')
            ->withImageFromFixture(self::FAVICON_URL, 32);

        self::assertNull((new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL));
    }

    public function testUnreachablePageStillTriesFaviconFallback(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withImageFromFixture(self::FAVICON_URL, 96);

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('favicon.ico', $selected->tier);
    }

    public function testUnreachableCandidateIsSkippedInFavorOfTheNextOne(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::PAGE_URL, <<<'HTML'
                <html><head>
                    <link rel="apple-touch-icon" href="/missing.png">
                    <link rel="apple-touch-icon" href="/apple.png">
                </head></html>
                HTML)
            ->withImageFromFixture('https://example.com/apple.png', 180);

        $selected = (new IconSelector($fetcher))->selectBestIcon(self::PAGE_URL);

        self::assertNotNull($selected);
        self::assertSame('https://example.com/apple.png', $selected->sourceUrl);
    }
}
