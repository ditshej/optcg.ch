<?php

declare(strict_types=1);

namespace Optcg\Tests\Data;

use Optcg\Data\Validator;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Yaml\Yaml;

#[CoversClass(Validator::class)]
final class ValidatorTest extends DataFixtureTestCase
{
    private const GOOD_ROOMS = [
        'rooms' => [
            [
                'id' => 'room-a',
                'name' => 'Room A',
                'topic' => 'Topic A',
                'description' => 'Room A description.',
                'order' => 1,
            ],
            [
                'id' => 'room-b',
                'name' => 'Room B',
                'topic' => 'Topic B',
                'description' => 'Room B description.',
                'order' => 2,
            ],
        ],
    ];

    public function testValidDataProducesNoErrors(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([$this->goodLink()]));
        $this->writeLinks('room-b', $this->linksYaml([]));

        $errors = $this->validate();

        self::assertSame([], $errors, $this->describe($errors));
    }

    public function testMissingRequiredFieldIsReported(): void
    {
        $link = $this->goodLink();
        unset($link['description']);

        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([$link]));

        $errors = $this->validate();

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $errors);
        self::assertStringContainsString('description', $error->message);
        self::assertStringContainsString('required', $error->message);
        self::assertNotSame('', $error->fix);
    }

    public function testNonHttpsUrlIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['url' => 'http://example.com/insecure']),
        ]));

        $errors = $this->validate();

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $errors);
        self::assertSame('good-link', $error->linkId);
        self::assertStringContainsString('url', $error->message);
    }

    public function testLinkFileWithUnknownRoomIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('no-such-room', $this->linksYaml([$this->goodLink()]));

        $errors = $this->validate();

        $roomErrors = array_values(array_filter(
            $errors,
            static fn ($e) => str_contains($e->message, "does not exist in data/rooms.yaml"),
        ));

        self::assertCount(1, $roomErrors, $this->describe($errors));
        self::assertSame('data/links/no-such-room.yaml', $roomErrors[0]->file);
    }

    public function testDuplicateLinkIdAcrossFilesIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['id' => 'shared-id', 'url' => 'https://example.com/a']),
        ]));
        $this->writeLinks('room-b', $this->linksYaml([
            $this->goodLink(['id' => 'shared-id', 'url' => 'https://example.com/b']),
        ]));

        $errors = $this->validate();

        $dupErrors = array_values(array_filter(
            $errors,
            static fn ($e) => str_contains($e->message, 'already used in'),
        ));

        self::assertCount(1, $dupErrors, $this->describe($errors));
        self::assertSame('data/links/room-b.yaml', $dupErrors[0]->file);
        self::assertSame('shared-id', $dupErrors[0]->linkId);
    }

    public function testDuplicateUrlIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['id' => 'link-one', 'url' => 'https://example.com/same']),
            $this->goodLink(['id' => 'link-two', 'url' => 'https://example.com/same']),
        ]));

        $errors = $this->validate();

        $dupErrors = array_values(array_filter(
            $errors,
            static fn ($e) => str_contains($e->message, 'already used by link'),
        ));

        self::assertCount(1, $dupErrors, $this->describe($errors));
        self::assertSame('link-two', $dupErrors[0]->linkId);
    }

    public function testTitleLongerThanMaximumLengthIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['title' => str_repeat('x', 81)]),
        ]));

        $errors = $this->validate();

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $errors);
        self::assertStringContainsString('title', $error->message);
    }

    public function testIconSourceMustBeFetchedOrManual(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['icon_source' => 'downloaded']),
        ]));

        $errors = $this->validate();

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $errors);
        self::assertStringContainsString('icon_source', $error->message);
    }

    public function testUnsupportedIconTypeIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['icon' => 'good-link.gif']),
        ]));

        $errors = $this->validate();

        $typeErrors = array_values(array_filter(
            $errors,
            static fn ($e) => str_contains($e->message, 'unsupported file type'),
        ));

        self::assertCount(1, $typeErrors, $this->describe($errors));
        self::assertSame('good-link', $typeErrors[0]->linkId);
        self::assertStringContainsString('PNG, SVG or WebP', $typeErrors[0]->fix);
    }

    public function testMissingIconFileIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['icon' => 'good-link.png']),
        ]));

        $errors = $this->validate();

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $errors);
        self::assertStringContainsString('does not exist', $error->message);
    }

    public function testNonSquareIconIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeNonSquarePng('good-link.png');
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['icon' => 'good-link.png']),
        ]));

        $errors = $this->validate();

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $errors);
        self::assertStringContainsString('not square', $error->message);
    }

    public function testOversizedIconIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeOversizedPng('good-link.png', 200 * 1024 + 1);
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['icon' => 'good-link.png']),
        ]));

        $errors = $this->validate();

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $errors);
        self::assertStringContainsString('larger than', $error->message);
    }

    public function testSquareIconWithinSizeLimitProducesNoError(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeSquarePng('good-link.png');
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['icon' => 'good-link.png']),
        ]));

        $errors = $this->validate();

        self::assertSame([], $errors, $this->describe($errors));
    }

    public function testTrackingParameterInUrlIsRejected(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['url' => 'https://example.com/good?utm_source=newsletter']),
        ]));

        $errors = $this->validate();

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $errors);
        self::assertStringContainsString('tracking', $error->message);
        self::assertStringContainsString('utm_source', $error->message);
    }

    public function testErrorNamesFileLinkIdAndFix(): void
    {
        $this->writeRooms(Yaml::dump(self::GOOD_ROOMS, 4));
        $this->writeLinks('room-a', $this->linksYaml([
            $this->goodLink(['url' => 'http://example.com/insecure']),
        ]));

        $error = $this->assertSingleErrorFor('data/links/room-a.yaml', $this->validate());
        $rendered = (string) $error;

        self::assertStringContainsString('data/links/room-a.yaml', $rendered);
        self::assertStringContainsString('good-link', $rendered);
        self::assertStringContainsString('Fix:', $rendered);
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function goodLink(array $overrides = []): array
    {
        return array_merge([
            'id' => 'good-link',
            'title' => 'Good Link',
            'url' => 'https://example.com/good',
            'description' => 'A good example link.',
            'language' => 'en',
            'icon_source' => 'manual',
            'tags' => ['example'],
            'featured' => false,
        ], $overrides);
    }

    /**
     * @param list<array<string, mixed>> $links
     */
    private function linksYaml(array $links): string
    {
        return Yaml::dump(['links' => $links], 4);
    }

    /**
     * @return \Optcg\Data\ValidationError[]
     */
    private function validate(): array
    {
        return (new Validator($this->paths()))->validate();
    }

    /**
     * @param \Optcg\Data\ValidationError[] $errors
     */
    private function assertSingleErrorFor(string $file, array $errors): \Optcg\Data\ValidationError
    {
        $matching = array_values(array_filter($errors, static fn ($e) => $e->file === $file));
        self::assertCount(1, $matching, $this->describe($errors));

        return $matching[0];
    }

    /**
     * @param \Optcg\Data\ValidationError[] $errors
     */
    private function describe(array $errors): string
    {
        return "Errors:\n" . implode("\n", array_map(static fn ($e) => (string) $e, $errors));
    }
}
