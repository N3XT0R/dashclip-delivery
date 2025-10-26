<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Clip;
use App\Models\Video;
use App\Services\VideoService;
use Tests\DatabaseTestCase;

class VideoServiceTest extends DatabaseTestCase
{
    protected VideoService $videoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->videoService = $this->app->make(VideoService::class);
    }


    public function testIsDuplicateReturnsFalse(): void
    {
        self::assertFalse($this->videoService->isDuplicate('non_existent_hash'));
    }

    public function testIsDuplicateReturnsTrue(): void
    {
        $hash = hash('sha256', 'duplicate_video_content');
        Video::factory()->create(['hash' => $hash]);
        self::assertTrue($this->videoService->isDuplicate($hash));
    }

    public function testCreateClipForVideoWorks(): void
    {
        $video = Video::factory()->create();
        $startSec = 10;
        $endSec = 20;
        $clip = $this->videoService->createClipForVideo($video, $startSec, $endSec);
        self::assertSame($startSec, $clip->start_sec);
        self::assertSame($endSec, $clip->end_sec);
        self::assertSame($video->id, $clip->video_id);
    }


    public function testGetClipForVideoWorks(): void
    {
        $video = Video::factory()->create();
        $clip = Clip::factory()->create(['start_sec' => 5, 'end_sec' => 15, 'video_id' => $video->getKey()]);
        $fetchedClip = $this->videoService->getClipForVideo($video, 5, 15);
        self::assertNotNull($fetchedClip);
        self::assertSame($clip->id, $fetchedClip->id);
    }
}