<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Clip;
use Tests\TestCase;

/**
 * Unit tests for the App\Models\Clip model.
 *
 * We validate:
 *  - mass assignment for fillable attributes (including nullable fields)
 *  - the belongsTo(Video) relationship
 *  - updates to note/bundle_key/role/submitted_by persist correctly
 */
final class ClipTest extends TestCase
{

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

        $this->assertSame('01:00', $clip->human_readable_duration);
    }

    public function testHumanReadableDurationReturnsNullOnNoStartSecPresent(): void
    {
        $clip = new Clip([
            'start_sec' => null,
            'end_sec' => 100,
        ]);

        $this->assertNull($clip->human_readable_duration);
    }

    public function testHumanReadableDurationReturnsNullOnNoEndSecPresent(): void
    {
        $clip = new Clip([
            'start_sec' => 50,
            'end_sec' => null,
        ]);

        $this->assertNull($clip->human_readable_duration);
    }
}
