<?php

declare(strict_types=1);

namespace Optcg\Tests\View;

use Optcg\View\IconResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(IconResolver::class)]
final class IconResolverTest extends TestCase
{
    public function testFilenameDefaultsToIdDotPng(): void
    {
        $resolver = new IconResolver(static fn (string $f): bool => false);

        self::assertSame('bandai-tcg-plus.png', $resolver->filename(['id' => 'bandai-tcg-plus']));
    }

    public function testFilenameUsesExplicitIcon(): void
    {
        $resolver = new IconResolver(static fn (string $f): bool => false);

        self::assertSame('custom.svg', $resolver->filename(['id' => 'example', 'icon' => 'custom.svg']));
    }

    public function testHasIconAsksTheGivenClosure(): void
    {
        $resolver = new IconResolver(static fn (string $f): bool => $f === 'example.png');

        self::assertTrue($resolver->hasIcon(['id' => 'example']));
        self::assertFalse($resolver->hasIcon(['id' => 'other']));
    }

    public function testInitialIsTheUppercasedFirstLetter(): void
    {
        $resolver = new IconResolver(static fn (string $f): bool => false);

        self::assertSame('E', $resolver->initial(['title' => 'egmanevents']));
    }

    public function testInitialFallsBackToQuestionMarkForAnEmptyTitle(): void
    {
        $resolver = new IconResolver(static fn (string $f): bool => false);

        self::assertSame('?', $resolver->initial(['title' => '']));
    }
}
