<?php

declare(strict_types=1);

namespace Optcg\Tests\View;

use Optcg\View\FolderPreview;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(FolderPreview::class)]
final class FolderPreviewTest extends TestCase
{
    public function testUpToFourLinksFillLargeSlotsAndPadsTheRest(): void
    {
        $links = [
            ['id' => 'a', 'title' => 'A', 'featured' => false],
            ['id' => 'b', 'title' => 'B', 'featured' => false],
        ];

        $layout = FolderPreview::layout($links);

        self::assertSame(['a', 'b', null, null], array_map(
            static fn (?array $link): ?string => $link['id'] ?? null,
            $layout['large'],
        ));
        self::assertNull($layout['small']);
    }

    public function testMoreThanFourLinksCollapseTheFourthSlotIntoASmallGrid(): void
    {
        $links = array_map(
            static fn (int $i): array => ['id' => "link-{$i}", 'title' => "Link {$i}", 'featured' => false],
            range(1, 6),
        );

        $layout = FolderPreview::layout($links);

        self::assertSame(['link-1', 'link-2', 'link-3'], array_map(
            static fn (array $link): string => $link['id'],
            $layout['large'],
        ));
        self::assertSame(['link-4', 'link-5', 'link-6', null], array_map(
            static fn (?array $link): ?string => $link['id'] ?? null,
            $layout['small'],
        ));
    }

    public function testFeaturedLinksTakePriorityOverFileOrder(): void
    {
        $links = [
            ['id' => 'plain-1', 'title' => 'Plain 1', 'featured' => false],
            ['id' => 'featured-1', 'title' => 'Featured 1', 'featured' => true],
            ['id' => 'plain-2', 'title' => 'Plain 2', 'featured' => false],
            ['id' => 'featured-2', 'title' => 'Featured 2', 'featured' => true],
        ];

        $layout = FolderPreview::layout($links);

        self::assertSame(['featured-1', 'featured-2', null, null], array_map(
            static fn (?array $link): ?string => $link['id'] ?? null,
            $layout['large'],
        ));
    }
}
