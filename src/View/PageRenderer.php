<?php

declare(strict_types=1);

namespace Optcg\View;

/**
 * Builds the view model for the overview page and renders it through the
 * plain PHP templates in views/ (no build step, following tcg-prizing).
 */
final class PageRenderer
{
    private const VIEWS_DIR = __DIR__ . '/../../views';

    public function __construct(
        private readonly IconResolver $icons,
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $rooms sorted by order (Loader::loadRooms())
     * @param array<string, array<int, array<string, mixed>>> $linksByRoomId room id => links (Loader::loadLinks())
     */
    public function render(array $rooms, array $linksByRoomId): string
    {
        $roomViews = array_map(
            fn (array $room): array => $this->buildRoomView($room, $linksByRoomId[$room['id']] ?? []),
            $rooms,
        );

        $icons = $this->icons;

        return $this->include('layout.php', ['roomViews' => $roomViews, 'icons' => $icons]);
    }

    /**
     * @param array<string, mixed> $room
     * @param array<int, array<string, mixed>> $links
     * @return array<string, mixed>
     */
    private function buildRoomView(array $room, array $links): array
    {
        if ($room['id'] === AddShopLink::ROOM_ID) {
            $links[] = AddShopLink::entry();
        }

        return [
            'room' => $room,
            'links' => $links,
            'color' => RoomColors::forRoom((string) $room['id']),
            'preview' => FolderPreview::layout($links),
        ];
    }

    /**
     * @param array<string, mixed> $vars
     */
    private function include(string $template, array $vars): string
    {
        $render = function (string $__template, array $__vars): string {
            extract($__vars, EXTR_SKIP);
            ob_start();
            require self::VIEWS_DIR . '/' . $__template;

            return (string) ob_get_clean();
        };

        return $render($template, $vars);
    }
}
