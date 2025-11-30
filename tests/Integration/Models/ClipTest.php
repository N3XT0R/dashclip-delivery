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

        $hash = md5($clip->video->getKey().'_'.$clip->start_sec.'_'.$clip->end_sec);
        $expected = "previews/{$hash}.mp4";

        $this->assertSame($expected, $clip->getPreviewPath());
    }
}
