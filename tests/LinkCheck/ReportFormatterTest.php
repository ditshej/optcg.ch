<?php

declare(strict_types=1);

namespace Optcg\Tests\LinkCheck;

use Optcg\LinkCheck\LinkCheckResult;
use Optcg\LinkCheck\ReportFormatter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReportFormatter::class)]
final class ReportFormatterTest extends TestCase
{
    private function ok(string $room, string $id): LinkCheckResult
    {
        return new LinkCheckResult($room, $id, ucfirst($id), "https://example.com/{$id}", true, 'HEAD', 200, null);
    }

    private function dead(string $room, string $id, ?int $statusCode, ?string $error): LinkCheckResult
    {
        return new LinkCheckResult($room, $id, ucfirst($id), "https://example.com/{$id}", false, 'GET', $statusCode, $error);
    }

    public function testSummaryWithoutDeadLinks(): void
    {
        $summary = (new ReportFormatter())->summary([$this->ok('figurehead', 'official')]);

        self::assertSame('Checked 1 link(s), 0 dead.', $summary);
    }

    public function testSummaryListsEachDeadLink(): void
    {
        $results = [
            $this->ok('figurehead', 'official'),
            $this->dead('library', 'rules-site', 404, null),
        ];

        $summary = (new ReportFormatter())->summary($results);

        self::assertStringContainsString('Checked 2 link(s), 1 dead.', $summary);
        self::assertStringContainsString('DEAD library/rules-site: https://example.com/rules-site -> HTTP 404 (via GET)', $summary);
    }

    public function testDeadLinksFiltersOutOkResults(): void
    {
        $results = [$this->ok('figurehead', 'official'), $this->dead('library', 'rules-site', 500, null)];

        $dead = (new ReportFormatter())->deadLinks($results);

        self::assertCount(1, $dead);
        self::assertSame('rules-site', $dead[0]->linkId);
    }

    public function testMarkdownReportWithoutDeadLinksHasNoDeadLinksSection(): void
    {
        $report = (new ReportFormatter())->markdownReport([$this->ok('figurehead', 'official')], '2026-10-12 06:17 UTC');

        self::assertStringContainsString('0 link(s) are unreachable', $report);
        self::assertStringNotContainsString('## Dead links', $report);
    }

    public function testMarkdownReportListsDeadLinksWithRoomIdAndReason(): void
    {
        $results = [$this->dead('library', 'rules-site', 404, null)];

        $report = (new ReportFormatter())->markdownReport($results, '2026-10-12 06:17 UTC');

        self::assertStringContainsString('## Dead links', $report);
        self::assertStringContainsString('**library / rules-site**', $report);
        self::assertStringContainsString('`https://example.com/rules-site`', $report);
        self::assertStringContainsString('HTTP 404 (via GET)', $report);
        self::assertStringContainsString('2026-10-12 06:17 UTC', $report);
    }

    public function testMarkdownReportUsesConnectionErrorAsReasonWhenNoStatusCode(): void
    {
        $results = [$this->dead('library', 'rules-site', null, 'Connection timed out')];

        $report = (new ReportFormatter())->markdownReport($results, '2026-10-12 06:17 UTC');

        self::assertStringContainsString('Connection timed out (via GET)', $report);
    }
}
