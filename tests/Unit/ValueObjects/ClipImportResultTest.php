<?php

declare(strict_types=1);

namespace Tests\Unit\ValueObjects;

use App\Models\Clip;
use App\Models\Video;
use App\ValueObjects\ClipImportResult;
use Illuminate\Support\Collection;
use Tests\TestCase;

class ClipImportResultTest extends TestCase
{
    protected ClipImportResult $importResult;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importResult = ClipImportResult::empty();
    }

    public function testEmptyInitializesWithZeroStatsAndEmptyCollections(): void
    {
        $arr = $this->importResult->toArray();

        // Struktur
        self::assertArrayHasKey('stats', $arr);
        self::assertArrayHasKey('created_ids', $arr);
        self::assertArrayHasKey('updated_ids', $arr);

        // Collections leer
        self::assertSame([], $arr['created_ids']);
        self::assertSame([], $arr['updated_ids']);

        // Stats sind alle 0 (ohne sich auf konkrete Keys festzulegen: Summe == 0)
        self::assertSame(0, array_sum($arr['stats']));
    }

    public function testAddCreatedAddsClipAndIncrementsStats(): void
    {
        $before = $this->importResult->toArray();

        $clip = new Clip();
        $clip->forceFill(['id' => 1, 'video_id' => 42]);

        $this->importResult->addCreated($clip);

        $after = $this->importResult->toArray();

        // created_ids enthält Clip-ID
        self::assertSame([1], $after['created_ids']);
        // updated_ids bleibt leer
        self::assertSame([], $after['updated_ids']);

        // Stats-Gesamtsumme +1
        self::assertSame(array_sum($before['stats']) + 1, array_sum($after['stats']));
    }

    public function testAddUpdatedAddsClipAndIncrementsStats(): void
    {
        $before = $this->importResult->toArray();

        $clip = new Clip();
        $clip->forceFill(['id' => 2, 'video_id' => 99]);

        $this->importResult->addUpdated($clip);

        $after = $this->importResult->toArray();

        // updated_ids enthält Clip-ID
        self::assertSame([2], $after['updated_ids']);
        // created_ids bleibt leer
        self::assertSame([], $after['created_ids']);

        // Stats-Gesamtsumme +1
        self::assertSame(array_sum($before['stats']) + 1, array_sum($after['stats']));
    }

    public function testIncrementWarningsIncrementsStatsWithoutAddingClips(): void
    {
        $before = $this->importResult->toArray();

        $this->importResult->incrementWarnings();

        $after = $this->importResult->toArray();

        // Keine neuen Clip-IDs
        self::assertSame([], $after['created_ids']);
        self::assertSame([], $after['updated_ids']);

        // Stats-Gesamtsumme +1
        self::assertSame(array_sum($before['stats']) + 1, array_sum($after['stats']));
    }

    public function testToArrayReturnsExpectedIds(): void
    {
        $clip1 = new Clip();
        $clip1->forceFill(['id' => 10]);

        $clip2 = new Clip();
        $clip2->forceFill(['id' => 20]);

        $this->importResult->addCreated($clip1);
        $this->importResult->addUpdated($clip2);

        $arr = $this->importResult->toArray();

        self::assertSame([10], $arr['created_ids']);
        self::assertSame([20], $arr['updated_ids']);
        self::assertIsArray($arr['stats']); // Struktur vorhanden
    }

    public function testMergeCombinesStatsAndCollections(): void
    {
        $a = ClipImportResult::empty();
        $b = ClipImportResult::empty();

        $c1 = new Clip();
        $c1->forceFill(['id' => 1]);
        $c2 = new Clip();
        $c2->forceFill(['id' => 2]);

        // A: 1 created
        $a->addCreated($c1);
        // B: 1 updated + 1 warning
        $b->addUpdated($c2);
        $b->incrementWarnings();

        $beforeA = $a->toArray();
        $beforeB = $b->toArray();

        $a->merge($b);
        $after = $a->toArray();

        // IDs zusammengeführt
        self::assertSame([1], $after['created_ids']);
        self::assertSame([2], $after['updated_ids']);

        // Stats summieren sich
        self::assertSame(
            array_sum($beforeA['stats']) + array_sum($beforeB['stats']),
            array_sum($after['stats'])
        );
    }

    public function testClipsForVideoFiltersByVideoId(): void
    {
        $video = new Video();
        $video->forceFill(['id' => 5]);

        $clipA = new Clip();
        $clipA->forceFill(['id' => 1, 'video_id' => 5]);
        $clipB = new Clip();
        $clipB->forceFill(['id' => 2, 'video_id' => 6]);

        $this->importResult->addCreated($clipA);
        $this->importResult->addUpdated($clipB);

        $clips = $this->importResult->clipsForVideo($video);

        self::assertInstanceOf(Collection::class, $clips);
        self::assertCount(1, $clips);
        self::assertSame($clipA, $clips->first());
    }

    public function testAllClipsReturnsCreatedPlusUpdated(): void
    {
        $clip1 = new Clip();
        $clip1->forceFill(['id' => 10]);
        $clip2 = new Clip();
        $clip2->forceFill(['id' => 20]);

        $this->importResult->addCreated($clip1);
        $this->importResult->addUpdated($clip2);

        $all = $this->importResult->allClips();
        
        self::assertCount(2, $all);
        self::assertSame([$clip1, $clip2], $all->all());
    }
}
