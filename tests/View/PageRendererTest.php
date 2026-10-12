<?php

declare(strict_types=1);

namespace Optcg\Tests\View;

use Optcg\View\IconResolver;
use Optcg\View\PageRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(PageRenderer::class)]
final class PageRendererTest extends TestCase
{
    private const ROOM_IDS = [
        'figurehead', 'navigation-room', 'crows-nest', 'library',
        'workshop', 'galley', 'lawn-deck', 'aquarium-bar',
    ];

    public function testRendersAllEightRoomsAsFoldersAndSections(): void
    {
        $html = $this->render($this->rooms(), []);

        self::assertSame(8, substr_count($html, 'class="folder"'));

        foreach (self::ROOM_IDS as $id) {
            self::assertStringContainsString('id="' . $id . '"', $html);
            self::assertStringContainsString('href="#' . $id . '"', $html);
        }
    }

    public function testLinkTilesUseSafeExternalAttributes(): void
    {
        $links = ['figurehead' => [$this->link('official-site', 'Official Site', 'https://onepiece-cardgame.com')]];

        $html = $this->render($this->rooms(), $links);

        self::assertStringContainsString(
            '<a href="https://onepiece-cardgame.com" target="_blank" rel="noopener">',
            $html,
        );
    }

    public function testGermanLinksGetALanguageBadge(): void
    {
        $links = ['library' => [$this->link('de-site', 'Deutsche Seite', 'https://example.de', 'de')]];

        $html = $this->render($this->rooms(), $links);

        self::assertStringContainsString('<span class="lang-badge">DE</span>', $html);
    }

    public function testEnglishLinksGetNoLanguageBadge(): void
    {
        $links = ['library' => [$this->link('en-site', 'English Site', 'https://example.com', 'en')]];

        $html = $this->render($this->rooms(), $links);

        self::assertStringNotContainsString('lang-badge', $html);
    }

    public function testLinkWithAnExistingIconFileRendersAnImgTag(): void
    {
        $links = ['workshop' => [$this->link('prizing', 'TCG Prizing', 'https://prizing.optcg.ch')]];
        $html = $this->render($this->rooms(), $links, static fn (string $f): bool => $f === 'prizing.png');

        self::assertStringContainsString('<img src="icons/prizing.png"', $html);
    }

    public function testLinkWithoutAnIconFileFallsBackToTheInitialLetterTile(): void
    {
        $links = ['workshop' => [$this->link('two-moons', 'Two Moons', 'https://twomoons.example')]];
        $html = $this->render($this->rooms(), $links, static fn (string $f): bool => false);

        self::assertStringContainsString('class="tile-fallback" style="background-color: #9c5518"', $html);
        self::assertStringContainsString('>T</span>', $html);
    }

    public function testShopsRoomAlwaysOffersTheAddYourShopTile(): void
    {
        $html = $this->render($this->rooms(), []);

        self::assertStringContainsString('https://github.com/ditshej/optcg.ch/issues/new', $html);
        self::assertStringContainsString('Add your shop', $html);
    }

    public function testMoreThanFourLinksCollapseTheFourthFolderSlot(): void
    {
        $links = ['crows-nest' => array_map(
            fn (int $i): array => $this->link("link-{$i}", "Link {$i}", "https://example.com/{$i}"),
            range(1, 5),
        )];

        $html = $this->render($this->rooms(), $links);

        self::assertStringContainsString('folder-slot-group', $html);
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $linksByRoomId
     */
    private function render(array $rooms, array $linksByRoomId, ?\Closure $iconExists = null): string
    {
        $icons = new IconResolver($iconExists ?? static fn (string $f): bool => false);

        return (new PageRenderer($icons))->render($rooms, $linksByRoomId);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function rooms(): array
    {
        $rooms = [];
        foreach (array_values(self::ROOM_IDS) as $order => $id) {
            $rooms[] = [
                'id' => $id,
                'name' => ucwords(str_replace('-', ' ', $id)),
                'topic' => 'Topic',
                'character' => 'Character',
                'description' => 'Description.',
                'order' => $order + 1,
            ];
        }

        return $rooms;
    }

    /**
     * @return array<string, mixed>
     */
    private function link(string $id, string $title, string $url, string $language = 'en'): array
    {
        return [
            'id' => $id,
            'title' => $title,
            'url' => $url,
            'description' => 'A link.',
            'language' => $language,
            'tags' => [],
            'featured' => false,
        ];
    }
}
