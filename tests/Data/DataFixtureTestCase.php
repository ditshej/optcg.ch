<?php

declare(strict_types=1);

namespace Optcg\Tests\Data;

use Optcg\Data\DataPaths;
use PHPUnit\Framework\TestCase;

/**
 * Builds a throwaway, self-contained data directory per test (its own
 * data/rooms.yaml, data/links/*.yaml, data/schema/*.json and public/link-icons/)
 * so Validator/Loader tests do not depend on, or affect, the real data set.
 */
abstract class DataFixtureTestCase extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();

        $this->root = sys_get_temp_dir() . '/optcg-data-test-' . bin2hex(random_bytes(8));
        mkdir($this->root . '/data/schema', recursive: true);
        mkdir($this->root . '/data/links', recursive: true);
        mkdir($this->root . '/public/link-icons', recursive: true);

        $schemaDir = dirname(__DIR__, 2) . '/data/schema';
        copy($schemaDir . '/rooms.schema.json', $this->root . '/data/schema/rooms.schema.json');
        copy($schemaDir . '/links.schema.json', $this->root . '/data/schema/links.schema.json');
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->root);
        parent::tearDown();
    }

    protected function paths(): DataPaths
    {
        return new DataPaths($this->root);
    }

    protected function writeRooms(string $yaml): void
    {
        file_put_contents($this->root . '/data/rooms.yaml', $yaml);
    }

    protected function writeLinks(string $roomId, string $yaml): void
    {
        file_put_contents($this->root . "/data/links/{$roomId}.yaml", $yaml);
    }

    protected function writeSquarePng(string $filename, int $size = 32): void
    {
        $image = imagecreatetruecolor($size, $size);
        imagepng($image, $this->root . '/public/link-icons/' . $filename);
    }

    protected function writeNonSquarePng(string $filename): void
    {
        $image = imagecreatetruecolor(64, 32);
        imagepng($image, $this->root . '/public/link-icons/' . $filename);
    }

    protected function writeOversizedPng(string $filename, int $minBytes): void
    {
        // A large solid-noise image compresses poorly, which keeps the PNG above $minBytes.
        $size = 600;
        $image = imagecreatetruecolor($size, $size);
        for ($x = 0; $x < $size; $x++) {
            for ($y = 0; $y < $size; $y++) {
                imagesetpixel($image, $x, $y, imagecolorallocate($image, random_int(0, 255), random_int(0, 255), random_int(0, 255)));
            }
        }
        imagepng($image, $this->root . '/public/link-icons/' . $filename, 0);

        if (filesize($this->root . '/public/link-icons/' . $filename) < $minBytes) {
            self::fail('Test setup problem: generated icon is smaller than expected, adjust writeOversizedPng().');
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $items = scandir($dir) ?: [];
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir . '/' . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
