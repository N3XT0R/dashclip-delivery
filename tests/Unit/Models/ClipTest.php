<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Clip;
use App\Models\Video;
use Tests\DatabaseTestCase;

/**
 * Unit tests for the App\Models\Clip model.
 *
 * We validate:
 *  - mass assignment for fillable attributes (including nullable fields)
 *  - the belongsTo(Video) relationship
 *  - updates to note/bundle_key/role/submitted_by persist correctly
 */
final class ClipTest extends DatabaseTestCase
{
    public function testMassAssignmentAllowsNullablesAndPersistsValues(): void
    {
        $video = Video::factory()->create();

        $clip = Clip::query()->create([
            'video_id' => $video->getKey(),
            'start_sec' => null,
            'end_sec' => null,
            'note' => null,
            'bundle_key' => null,
            'role' => null,
            'submitted_by' => null,
        ])->fresh();

        // Persisted foreign key
        $this->assertSame($video->getKey(), $clip->video_id);

        // Nullable fields are stored as null
        $this->assertNull($clip->start_sec);
        $this->assertNull($clip->end_sec);
        $this->assertNull($clip->note);
        $this->assertNull($clip->bundle_key);
        $this->assertNull($clip->role);
        $this->assertNull($clip->submitted_by);
    }

    public function testBelongsToVideoResolvesParent(): void
    {
        $video = Video::factory()->create();

        $clip = Clip::factory()->create([
            'video_id' => $video->getKey(),
            'start_sec' => 5,
            'end_sec' => 15,
        ]);

        $parent = $clip->video;

        $this->assertNotNull($parent);
        $this->assertSame($video->getKey(), $parent->getKey());
    }

    public function testUpdatingMetadataFieldsPersists(): void
    {
        $video = Video::factory()->create();

        $clip = Clip::factory()->create([
            'video_id' => $video->getKey(),
            'start_sec' => 10,
            'end_sec' => 20,
            'note' => 'orig',
            'bundle_key' => 'B1',
            'role' => 'F',
            'submitted_by' => 'alice',
        ]);

        $clip->update([
            'note' => 'updated note',
            'bundle_key' => 'B2',
            'role' => 'R',
            'submitted_by' => 'bob',
        ]);

        $fresh = $clip->fresh();
        $this->assertSame('updated note', $fresh->note);
        $this->assertSame('B2', $fresh->bundle_key);
        $this->assertSame('R', $fresh->role);
        $this->assertSame('bob', $fresh->submitted_by);
    }

    public function testStartTimeFormatsSecondsToMinutesAndSeconds(): void
    {
        $clip = new Clip([
            'start_sec' => 125, // 2:05
        ]);

        $this->assertSame('02:05', $clip->start_time);
    }

    public function testStartTimeFormatsZeroSeconds(): void
    {
        $clip = new Clip([
            'start_sec' => 0,
        ]);

        $this->assertSame('00:00', $clip->start_time);
    }

    public function testStartTimeReturnsNullWhenStartSecIsNull(): void
    {
        $clip = new Clip([
            'start_sec' => null,
        ]);

        $this->assertNull($clip->start_time);
    }

    public function testDurationCalculatesDurationCorrectly(): void
    {
        $clip = new Clip([
            'start_sec' => 10,
            'end_sec' => 25,
        ]);

        $this->assertSame(15, $clip->duration);
    }

    public function testDurationReturnsNullWhenStartSecIsNull(): void
    {
        $clip = new Clip([
            'start_sec' => null,
            'end_sec' => 100,
        ]);

        $this->assertNull($clip->duration);
    }

    public function testDurationReturnsNullWhenEndSecIsNull(): void
    {
        $clip = new Clip([
            'start_sec' => 10,
            'end_sec' => null,
        ]);

        $this->assertNull($clip->duration);
    }

    public function testEndTimeFormatsEndSecToMinutesAndSeconds(): void
    {
        $clip = new Clip([
            'end_sec' => 125, // 02:05
        ]);

        $this->assertSame('02:05', $clip->end_time);
    }

    public function testEndTimeFormatsZeroSeconds(): void
    {
        $clip = new Clip([
            'end_sec' => 0,
        ]);

        $this->assertSame('00:00', $clip->end_time);
    }

    public function testEndTimeReturnsNullWhenEndSecIsNull(): void
    {
        $clip = new Clip([
            'end_sec' => null,
        ]);

        $this->assertNull($clip->end_time);
    }

    public function testEndTimeHandlesStringNumbers(): void
    {
        $clip = new Clip([
            'end_sec' => '75',
        ]);

        $this->assertSame('01:15', $clip->end_time);
    }

    public function testHumanReadableDurationReturnsMinuteAndSeconds(): void
    {
        $clip = new Clip([
            'start_sec' => 70,
            'end_sec' => 130,
        ]);

        $this->assertSame('1m 0s', $clip->human_readable_duration);
    }

    public function testHumanReadableDurationReturnsNull(): void
    {
        $clip = new Clip([
            'start_sec' => null,
            'end_sec' => 100,
        ]);

        $this->assertNull($clip->human_readable_duration);
    }
}
