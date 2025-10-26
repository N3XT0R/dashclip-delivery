<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

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
}