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
        'figurehead', 'lawn-deck', 'crows-nest', 'library',
        'usopp-factory', 'galley', 'sick-bay', 'soldier-dock',
        'aquarium-bar', 'helm',
    ];

    public function testRendersAllTenRoomsAsCalloutsZonesAndSections(): void
    {
        $html = $this->render($this->rooms(), []);

        // Landscape callouts (plan) and room zones in both drawings.
        self::assertSame(10, substr_count($html, 'class="callout" style="--i:'));
        self::assertSame(20, substr_count($html, 'class="zone"'));
        self::assertSame(10, substr_count($html, '"><a href="#'));

        foreach (self::ROOM_IDS as $id) {
            self::assertStringContainsString('id="' . $id . '"', $html);
            self::assertStringContainsString('href="#' . $id . '"', $html);
        }
    }

    public function testCalloutsAnnounceTopicRoomAndLinkCount(): void
    {
        $links = ['figurehead' => [$this->link('official-site', 'Official Site', 'https://onepiece-cardgame.com')]];

        $html = $this->render($this->rooms(), $links);

        self::assertMatchesRegularExpression('/aria-label="[^"]+, [^"]+, 1 link"/', $html);
        self::assertMatchesRegularExpression('/aria-label="[^"]+, [^"]+, 0 links"/', $html);
        self::assertStringContainsString('data-count="1 link"', $html);
    }

    public function testIntroAnimationIsGatedByLocalStorageNotCookies(): void
    {
        $html = $this->render($this->rooms(), []);

        self::assertStringContainsString("localStorage.getItem('optcg-intro')", $html);
        self::assertStringNotContainsString('document.cookie', $html);
        self::assertStringContainsString('pathLength="1" class="hull', $html);
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
        $links = ['usopp-factory' => [$this->link('prizing', 'TCG Prizing', 'https://prizing.optcg.ch')]];
        $html = $this->render($this->rooms(), $links, static fn (string $f): bool => $f === 'prizing.png');

        self::assertStringContainsString('<img src="icons/prizing.png"', $html);
    }

    public function testLinkWithoutAnIconFileFallsBackToTheInitialLetterTile(): void
    {
        $links = ['usopp-factory' => [$this->link('two-moons', 'Two Moons', 'https://twomoons.example')]];
        $html = $this->render($this->rooms(), $links, static fn (string $f): bool => false);

        self::assertStringContainsString('class="tile-fallback" style="background-color: #9c5518"', $html);
        self::assertStringContainsString('>T</span>', $html);
    }

    public function testSpecialCharactersInTitlesAreEscaped(): void
    {
        $links = ['library' => [$this->link('weird-title', 'Rules & "Errata" <v2>', 'https://example.com/rules')]];

        $html = $this->render($this->rooms(), $links);

        self::assertStringContainsString('Rules &amp; &quot;Errata&quot; &lt;v2&gt;', $html);
        self::assertStringNotContainsString('<v2>', $html);
    }

    public function testShopsRoomAlwaysOffersTheAddYourShopTile(): void
    {
        $html = $this->render($this->rooms(), []);

        self::assertStringContainsString('https://github.com/ditshej/optcg.ch/issues/new?template=add-your-shop.yml', $html);
        self::assertStringContainsString('Add your shop', $html);
    }

    public function testRendersTheSheetDialogAndEnhancementScript(): void
    {
        $html = $this->render($this->rooms(), []);

        self::assertStringContainsString('<dialog id="sheet"', $html);
        self::assertStringContainsString('id="sheet-close"', $html);
        self::assertStringContainsString('<script src="js/sunny.js" defer></script>', $html);
    }

    public function testShipHasTwoMastsAndNoPaddleWheels(): void
    {
        $html = $this->render($this->rooms(), []);

        // One drawing per view (plan and portrait), two mast paths in each.
        self::assertSame(4, substr_count($html, 'class="mast"'));
        self::assertStringNotContainsString('wheel', $html);
    }

    public function testNoExternalRequestsFromTheMarkup(): void
    {
        $html = $this->render($this->rooms(), []);

        self::assertDoesNotMatchRegularExpression('/(src|href)="https?:\/\/[^"]*"(?![^>]*target="_blank")/', $html);
        self::assertStringNotContainsString('<script src="http', $html);
        self::assertStringNotContainsString('@import', $html);
    }

    public function testCalloutsShowTheTopicNotTheRoomName(): void
    {
        $rooms = $this->rooms();
        $rooms[5]['topic'] = 'Shops';

        $html = $this->render($rooms, []);

        self::assertStringContainsString('>SHOPS</text>', $html);
    }

    public function testRoomWithoutAPlaceOnTheShipIsRejected(): void
    {
        $rooms = $this->rooms();
        $rooms[0]['id'] = 'brig';

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('brig');

        $this->render($rooms, []);
    }

    public function testLinkDescriptionIsShownBesideTheTile(): void
    {
        $links = ['library' => [$this->link('official-rules', 'Official Rules', 'https://example.com/rules')]];

        $html = $this->render($this->rooms(), $links);

        self::assertStringContainsString('<span class="link-description">A link.</span>', $html);
    }

    public function testRoomDescriptionAppearsAboveTheLinksInTheOverlay(): void
    {
        $html = $this->render($this->rooms(), []);

        self::assertStringContainsString('<p class="room-description">Description.</p>', $html);
    }

    public function testSuggestLinkTileIsOfferedInEveryRoomExceptShops(): void
    {
        $html = $this->render($this->rooms(), []);

        foreach (self::ROOM_IDS as $id) {
            $section = $this->roomSection($html, $id);

            if ($id === 'galley') {
                self::assertStringNotContainsString('Suggest a link', $section, 'galley (Shops) must not offer Suggest a link');
                continue;
            }

            self::assertStringContainsString('Suggest a link', $section, "{$id} is missing the Suggest a link tile");
            self::assertStringContainsString(
                'https://github.com/ditshej/optcg.ch/issues/new?template=suggest-a-link.yml&amp;room=',
                $section,
            );
        }
    }

    public function testSuggestLinkTileHasNoEmptyDescriptionLine(): void
    {
        $html = $this->render($this->rooms(), []);

        self::assertStringNotContainsString('<span class="link-description"></span>', $html);
    }

    public function testSuggestLinkTileIsLastInTheRoom(): void
    {
        $links = ['crows-nest' => [$this->link('one-link', 'One Link', 'https://example.com/one')]];

        $section = $this->roomSection($this->render($this->rooms(), $links), 'crows-nest');

        self::assertMatchesRegularExpression('/One Link.*Suggest a link/s', $section);
    }

    private function roomSection(string $html, string $roomId): string
    {
        self::assertMatchesRegularExpression(
            '/<section id="' . preg_quote($roomId, '/') . '".*?<\/section>/s',
            $html,
        );
        preg_match('/<section id="' . preg_quote($roomId, '/') . '".*?<\/section>/s', $html, $matches);

        return $matches[0];
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
