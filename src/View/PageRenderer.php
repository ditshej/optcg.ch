<?php

declare(strict_types=1);

namespace Optcg\View;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

/**
 * Builds the view model for the Sunny page and renders it through the
 * Twig templates in templates/ (no build step: Twig compiles templates to
 * PHP at request time, cache disabled, for plain-PHP shared hosting).
 */
final class PageRenderer
{
    private const TEMPLATES_DIR = __DIR__ . '/../../templates';
    private const LOGO_SVG_FILE = __DIR__ . '/../../public/assets/compass-rose.svg';

    private readonly Environment $twig;

    public function __construct(
        private readonly IconResolver $icons,
    ) {
        $this->twig = new Environment(new FilesystemLoader(self::TEMPLATES_DIR), [
            'autoescape' => 'html',
            'strict_variables' => true,
            'cache' => false,
        ]);
        $this->twig->addGlobal('discordProfileUrl', DiscordProfile::URL);
        $this->twig->addGlobal('suggestLinkIssueUrl', SuggestLinkEntry::ISSUE_TEMPLATE_URL);
    }

    /**
     * @param array<int, array<string, mixed>> $rooms sorted by order (Loader::loadRooms())
     * @param array<string, array<int, array<string, mixed>>> $linksByRoomId room id => links (Loader::loadLinks())
     */
    public function render(array $rooms, array $linksByRoomId): string
    {
        $roomViews = [];
        foreach (array_values($rooms) as $i => $room) {
            $roomViews[] = $this->buildRoomView($room, $linksByRoomId[$room['id']] ?? [], sprintf('%02d', $i + 1));
        }

        return $this->twig->render('layout.html.twig', [
            'roomViews' => $roomViews,
            'logoSvg' => (string) file_get_contents(self::LOGO_SVG_FILE),
            'shipDefs' => ShipDrawing::defs(),
            'shipDrawing' => ShipDrawing::svg(),
            'viewPlan' => ShipLayout::VIEW_PLAN,
            'viewShip' => ShipLayout::VIEW_SHIP,
            'labelW' => ShipLayout::LABEL_W,
            'labelH' => ShipLayout::LABEL_H,
        ]);
    }

    /**
     * @param array<string, mixed> $room
     * @param array<int, array<string, mixed>> $links
     * @return array<string, mixed>
     */
    private function buildRoomView(array $room, array $links, string $number): array
    {
        if ($room['id'] === AddShopLink::ROOM_ID) {
            $links[] = AddShopLink::entry();
        }

        $color = RoomColors::forRoom((string) $room['id']);

        if (SuggestLinkEntry::appliesTo($room)) {
            $links[] = SuggestLinkEntry::entry($room);
        }

        $toTile = fn (?array $link): ?array => TileVisual::view($link, $color, $this->icons);

        return [
            'room' => $room,
            'links' => array_map(
                fn (array $link): array => $link + ['tile' => $toTile($link)],
                $links,
            ),
            'color' => $color,
            'number' => $number,
            'ship' => ShipLayout::forRoom((string) $room['id']),
        ];
    }
}
