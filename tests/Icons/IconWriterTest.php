<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

use Optcg\Icons\ImageInspector;
use Optcg\Icons\IconWriter;
use Optcg\Icons\SelectedIcon;
use PHPUnit\Framework\TestCase;

final class IconWriterTest extends TestCase
{
    public function testWritesIconScaledToStoredSize(): void
    {
        $decoded = ImageInspector::decode(PngFixture::square(57));
        self::assertNotNull($decoded);

        $icon = new SelectedIcon($decoded->image, $decoded->width, $decoded->height, 'https://example.com/apple.png', 'apple-touch-icon');
        $targetPath = sys_get_temp_dir() . '/optcg-icon-writer-test-' . bin2hex(random_bytes(8)) . '.png';

        try {
            (new IconWriter())->write($icon, $targetPath);

            self::assertFileExists($targetPath);
            $info = getimagesize($targetPath);
            self::assertNotFalse($info);
            self::assertSame(IconWriter::STORED_SIZE_PX, $info[0]);
            self::assertSame(IconWriter::STORED_SIZE_PX, $info[1]);
        } finally {
            @unlink($targetPath);
        }
    }
}
