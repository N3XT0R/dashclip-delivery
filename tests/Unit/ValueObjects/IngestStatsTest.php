<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Enum\Ingest\IngestResult;
use App\ValueObjects\IngestStats;
use Tests\TestCase;

class IngestStatsTest extends TestCase
{
    protected IngestStats $ingestStats;

    protected function setUp(): void
    {
        parent::setUp();
        $this->ingestStats = new IngestStats();
    }

    public function testFromArrayInitializesCorrectly(): void
    {
        $data = ['new' => 5, 'dups' => 2, 'err' => 1];
        $stats = IngestStats::fromArray($data);

        self::assertSame(5, $stats->getNew());
        self::assertSame(2, $stats->getDups());
        self::assertSame(1, $stats->getErr());
    }

    public function testFromArrayHandlesMissingKeys(): void
    {
        $stats = IngestStats::fromArray([]);

        self::assertSame(0, $stats->getNew());
        self::assertSame(0, $stats->getDups());
        self::assertSame(0, $stats->getErr());
    }

    public function testIncrementIncreasesCounters(): void
    {
        $this->ingestStats->increment(IngestResult::NEW);
        $this->ingestStats->increment(IngestResult::NEW);
        $this->ingestStats->increment(IngestResult::DUPS);
        $this->ingestStats->increment(IngestResult::ERR);

        self::assertSame(2, $this->ingestStats->getNew());
        self::assertSame(1, $this->ingestStats->getDups());
        self::assertSame(1, $this->ingestStats->getErr());
    }

    public function testAddCombinesTwoInstances(): void
    {
        $a = IngestStats::fromArray(['new' => 2, 'dups' => 1, 'err' => 3]);
        $b = IngestStats::fromArray(['new' => 4, 'dups' => 0, 'err' => 2]);

        $a->add($b);

        self::assertSame(6, $a->getNew());
        self::assertSame(1, $a->getDups());
        self::assertSame(5, $a->getErr());
    }

    public function testTotalReturnsSum(): void
    {
        $stats = IngestStats::fromArray(['new' => 3, 'dups' => 4, 'err' => 2]);
        self::assertSame(9, $stats->total());
    }

    public function testIsEmptyReturnsTrueWhenAllZero(): void
    {
        $stats = new IngestStats();
        self::assertTrue($stats->isEmpty());
    }

    public function testIsEmptyReturnsFalseWhenAnyCountGreaterThanZero(): void
    {
        $stats = IngestStats::fromArray(['new' => 1]);
        self::assertFalse($stats->isEmpty());
    }

    public function testToArrayReturnsExpectedStructure(): void
    {
        $stats = IngestStats::fromArray(['new' => 1, 'dups' => 2, 'err' => 3]);

        $expected = [
            IngestResult::NEW->value => 1,
            IngestResult::DUPS->value => 2,
            IngestResult::ERR->value => 3,
        ];

        self::assertSame($expected, $stats->toArray());
    }

    public function testToStringReturnsFormattedString(): void
    {
        $stats = IngestStats::fromArray(['new' => 1, 'dups' => 2, 'err' => 3]);
        $expected = 'Neu: 1 | Doppelt: 2 | Fehler: 3';

        self::assertSame($expected, (string)$stats);
    }

    public function testGettersReturnCorrectValues(): void
    {
        $stats = IngestStats::fromArray(['new' => 7, 'dups' => 8, 'err' => 9]);

        self::assertSame(7, $stats->getNew());
        self::assertSame(8, $stats->getDups());
        self::assertSame(9, $stats->getErr());
    }

    public function testToStringFormatsOutputCorrectly(): void
    {
        $stats = IngestStats::fromArray(['new' => 3, 'dups' => 1, 'err' => 2]);
        $expected = 'Neu: 3 | Doppelt: 1 | Fehler: 2';

        self::assertSame($expected, (string)$stats);
    }

}
