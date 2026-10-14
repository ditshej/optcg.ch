<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

use Optcg\Icons\ImageInspector;
use PHPUnit\Framework\TestCase;

final class ImageInspectorTest extends TestCase
{
    public function testDecodesPlainPng(): void
    {
        $decoded = ImageInspector::decode(PngFixture::square(180));

        self::assertNotNull($decoded);
        self::assertSame(180, $decoded->width);
        self::assertSame(180, $decoded->height);
    }

    public function testDecodesLargestPngEmbeddedIcoEntry(): void
    {
        $decoded = ImageInspector::decode(IcoFixture::withPngEntries([16, 32, 64]));

        self::assertNotNull($decoded);
        self::assertSame(64, $decoded->width);
        self::assertSame(64, $decoded->height);
    }

    public function testRawBitmapIcoEntryIsNotDecodable(): void
    {
        self::assertNull(ImageInspector::decode(IcoFixture::withRawBitmapEntry(48)));
    }

    public function testGarbageBytesAreNotDecodable(): void
    {
        self::assertNull(ImageInspector::decode('not an image'));
    }

    public function testSvgBytesAreNotDecodable(): void
    {
        self::assertNull(ImageInspector::decode('<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64"></svg>'));
    }
}
