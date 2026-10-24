<?php

declare(strict_types=1);

namespace Optcg\Tests\Icons;

use Optcg\Icons\YouTubeChannelIcon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class YouTubeChannelIconTest extends TestCase
{
    private const CHANNEL_URL = 'https://www.youtube.com/@example';
    private const IMAGE_URL = 'https://yt3.googleusercontent.com/avatar=s900';

    /**
     * @return array<string, array{string, bool}>
     */
    public static function urls(): array
    {
        return [
            'handle' => ['https://www.youtube.com/@example', true],
            'handle without www' => ['https://youtube.com/@example', true],
            'mobile handle' => ['https://m.youtube.com/@example/', true],
            'channel id' => ['https://www.youtube.com/channel/UC123', true],
            'video' => ['https://www.youtube.com/watch?v=abc', false],
            'playlist' => ['https://www.youtube.com/playlist?list=abc', false],
            'other host' => ['https://example.com/@example', false],
            'lookalike host' => ['https://notyoutube.com/@example', false],
        ];
    }

    #[DataProvider('urls')]
    public function testRecognisesChannelUrls(string $url, bool $expected): void
    {
        self::assertSame($expected, YouTubeChannelIcon::isChannelUrl($url));
    }

    public function testReadsOgImage(): void
    {
        $html = '<html><head><meta property="og:title" content="x"><meta property="og:image" content="' . self::IMAGE_URL . '"></head></html>';

        self::assertSame(self::IMAGE_URL, YouTubeChannelIcon::ogImageUrl($html, self::CHANNEL_URL));
        self::assertNull(YouTubeChannelIcon::ogImageUrl('<html><head></head></html>', self::CHANNEL_URL));
    }

    public function testSelectsTheChannelImage(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml(self::CHANNEL_URL, '<meta property="og:image" content="' . self::IMAGE_URL . '">')
            ->withImageFromFixture(self::IMAGE_URL, 200);

        $selected = (new YouTubeChannelIcon($fetcher))->select(self::CHANNEL_URL);

        self::assertNotNull($selected);
        self::assertSame('youtube-channel', $selected->tier);
        self::assertSame(200, $selected->width);
    }

    public function testFallsBackWithoutAChannelImage(): void
    {
        $noOgImage = (new FakeHttpFetcher())->withHtml(self::CHANNEL_URL, '<html></html>');
        $unreachable = new FakeHttpFetcher();
        $tooSmall = (new FakeHttpFetcher())
            ->withHtml(self::CHANNEL_URL, '<meta property="og:image" content="' . self::IMAGE_URL . '">')
            ->withImageFromFixture(self::IMAGE_URL, 32);
        $broken = (new FakeHttpFetcher())
            ->withHtml(self::CHANNEL_URL, '<meta property="og:image" content="' . self::IMAGE_URL . '">')
            ->withImage(self::IMAGE_URL, 'not an image');

        foreach ([$noOgImage, $unreachable, $tooSmall, $broken] as $fetcher) {
            self::assertNull((new YouTubeChannelIcon($fetcher))->select(self::CHANNEL_URL));
        }
    }

    public function testIgnoresNonChannelUrls(): void
    {
        $fetcher = (new FakeHttpFetcher())
            ->withHtml('https://www.youtube.com/watch?v=abc', '<meta property="og:image" content="' . self::IMAGE_URL . '">')
            ->withImageFromFixture(self::IMAGE_URL, 200);

        self::assertNull((new YouTubeChannelIcon($fetcher))->select('https://www.youtube.com/watch?v=abc'));
    }
}
