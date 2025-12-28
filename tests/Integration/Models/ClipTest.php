<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use Tests\DatabaseTestCase;

final class ClipTest extends DatabaseTestCase
{
    public function testBelongsToVideoAndUser(): void
    {
        $user = User::factory()->create();
        $video = Video::factory()->create();

        $clip = Clip::factory()->create([
            'video_id' => $video->getKey(),
        ]);

        $clip->setUser($user)->save();

        $this->assertTrue($clip->video->is($video));
        $this->assertTrue($clip->user->is($user));
        $this->assertSame($user->display_name, $clip->submitted_by);
    }

    public function testAccessorsExposeTimesAndDuration(): void
    {
        $clip = Clip::factory()->create([
            'start_sec' => 30,
            'end_sec' => 75,
        ]);

        $this->assertSame('00:30', $clip->start_time);
        $this->assertSame('01:15', $clip->end_time);
        $this->assertSame(45, $clip->duration);
    }

    public function testGeneratesPreviewPathFromVideoAndTimestamps(): void
    {
        $clip = Clip::factory()->create([
            'start_sec' => 5,
            'end_sec' => 10,
        ]);

        $clip->load('video');

        $hash = md5($clip->video->getKey() . '_' . $clip->start_sec . '_' . $clip->end_sec);
        $expected = "previews/{$hash}.mp4";

        $this->assertSame($expected, $clip->getPreviewPath());
    }

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
}
