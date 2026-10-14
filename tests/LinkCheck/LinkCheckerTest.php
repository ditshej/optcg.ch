<?php

declare(strict_types=1);

namespace Optcg\Tests\LinkCheck;

use Optcg\LinkCheck\LinkChecker;
use Optcg\LinkCheck\ProbeResult;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LinkChecker::class)]
final class LinkCheckerTest extends TestCase
{
    private const LINK = [
        'room' => 'figurehead',
        'id' => 'official-website',
        'title' => 'Official Website',
        'url' => 'https://example.com/official',
    ];

    public function testHeadSuccessDoesNotFallBackToGet(): void
    {
        $prober = new FakeHttpProbe();
        $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: true, statusCode: 200, error: null));

        $result = (new LinkChecker($prober))->checkLink(self::LINK);

        self::assertTrue($result->ok);
        self::assertSame('HEAD', $result->method);
        self::assertSame(200, $result->statusCode);
        self::assertSame([['method' => 'HEAD', 'url' => self::LINK['url']]], $prober->calls);
    }

    public function testFallsBackToGetWhenHeadFails(): void
    {
        $prober = new FakeHttpProbe();
        $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: false, statusCode: 405, error: null));
        $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: false, statusCode: 405, error: null));
        $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: false, statusCode: 405, error: null));
        $prober->queue('GET', self::LINK['url'], new ProbeResult(ok: true, statusCode: 200, error: null));

        $result = (new LinkChecker($prober, retries: 2))->checkLink(self::LINK);

        self::assertTrue($result->ok);
        self::assertSame('GET', $result->method);
        self::assertSame(200, $result->statusCode);
    }

    public function testRetriesHeadBeforeFallingBackToGet(): void
    {
        $prober = new FakeHttpProbe();
        // First HEAD attempt fails, second (first retry) succeeds: GET must never be called.
        $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: false, statusCode: null, error: 'timed out'));
        $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: true, statusCode: 200, error: null));

        $result = (new LinkChecker($prober, retries: 2))->checkLink(self::LINK);

        self::assertTrue($result->ok);
        self::assertSame('HEAD', $result->method);
        self::assertCount(2, $prober->calls);
    }

    public function testDeadLinkAfterAllRetriesOnBothMethods(): void
    {
        $prober = new FakeHttpProbe();
        for ($i = 0; $i < 3; $i++) {
            $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: false, statusCode: 404, error: null));
            $prober->queue('GET', self::LINK['url'], new ProbeResult(ok: false, statusCode: 404, error: null));
        }

        $result = (new LinkChecker($prober, retries: 2))->checkLink(self::LINK);

        self::assertFalse($result->ok);
        self::assertSame('GET', $result->method);
        self::assertSame(404, $result->statusCode);
        self::assertSame('HTTP 404 (via GET)', $result->reason());
        self::assertCount(6, $prober->calls);
    }

    public function testConnectionErrorIsReportedInReason(): void
    {
        $prober = new FakeHttpProbe();
        for ($i = 0; $i < 3; $i++) {
            $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: false, statusCode: null, error: 'Connection timed out'));
            $prober->queue('GET', self::LINK['url'], new ProbeResult(ok: false, statusCode: null, error: 'Connection timed out'));
        }

        $result = (new LinkChecker($prober, retries: 2))->checkLink(self::LINK);

        self::assertFalse($result->ok);
        self::assertSame('Connection timed out (via GET)', $result->reason());
    }

    public function testChecksAllLinksIndependently(): void
    {
        $linkTwo = ['room' => 'library', 'id' => 'rules-site', 'title' => 'Rules Site', 'url' => 'https://example.com/rules'];

        $prober = new FakeHttpProbe();
        $prober->queue('HEAD', self::LINK['url'], new ProbeResult(ok: true, statusCode: 200, error: null));
        $prober->queue('HEAD', $linkTwo['url'], new ProbeResult(ok: false, statusCode: 500, error: null));
        $prober->queue('GET', $linkTwo['url'], new ProbeResult(ok: false, statusCode: 500, error: null));

        $results = (new LinkChecker($prober, retries: 0))->checkAll([self::LINK, $linkTwo]);

        self::assertCount(2, $results);
        self::assertTrue($results[0]->ok);
        self::assertFalse($results[1]->ok);
        self::assertSame('library', $results[1]->roomId);
    }
}
