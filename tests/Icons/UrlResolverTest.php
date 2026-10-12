<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

use Optcg\Icons\UrlResolver;
use PHPUnit\Framework\TestCase;

final class UrlResolverTest extends TestCase
{
    public function testAbsoluteUrlIsUnchanged(): void
    {
        self::assertSame(
            'https://other.example/icon.png',
            UrlResolver::resolve('https://example.com/page', 'https://other.example/icon.png'),
        );
    }

    public function testRootRelativeUrlUsesOrigin(): void
    {
        self::assertSame(
            'https://example.com/static/icon.png',
            UrlResolver::resolve('https://example.com/sub/page.html', '/static/icon.png'),
        );
    }

    public function testProtocolRelativeUrlKeepsBaseScheme(): void
    {
        self::assertSame(
            'https://cdn.example/icon.png',
            UrlResolver::resolve('https://example.com/page', '//cdn.example/icon.png'),
        );
    }

    public function testRelativeUrlIsResolvedAgainstDirectory(): void
    {
        self::assertSame(
            'https://example.com/sub/icon.png',
            UrlResolver::resolve('https://example.com/sub/page.html', 'icon.png'),
        );
    }

    public function testRelativeUrlWithParentSegmentIsNormalized(): void
    {
        self::assertSame(
            'https://example.com/icon.png',
            UrlResolver::resolve('https://example.com/sub/page.html', '../icon.png'),
        );
    }

    public function testBasePortIsPreserved(): void
    {
        self::assertSame(
            'https://example.com:8443/icon.png',
            UrlResolver::resolve('https://example.com:8443/page', '/icon.png'),
        );
    }
}
