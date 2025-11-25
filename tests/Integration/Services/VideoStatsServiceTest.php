<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use App\Services\VideoStatsService;
use Tests\DatabaseTestCase;

class VideoStatsServiceTest extends DatabaseTestCase
{
    protected VideoStatsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = $this->app->make(VideoStatsService::class);
    }

    public function testGetVideoStatsReturnsCorrectStat(): void
    {
        $user = User::factory()->create();

        // 3 Videos + Clips für den User
        foreach (range(1, 3) as $i) {
            $video = Video::factory()->create();
            Clip::factory()->create([
                'video_id' => $video->id,
                'user_id' => $user->id,
            ]);
        }

        $stat = $this->service->getVideoStats($user);

        $this->assertEquals('Videos', $stat->getLabel());
        $this->assertEquals('3', (string)$stat->getValue());
        $this->assertEquals('Videos insgesamt', $stat->getDescription());
        $this->assertEquals('primary', $stat->getColor());
    }

    public function testDownloadedVideoStats(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $batch = Batch::factory()->create();

        $video = Video::factory()->create();
        Clip::factory()->create([
            'video_id' => $video->id,
            'user_id' => $user->id,
        ]);

        Assignment::factory()->create([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'batch_id' => $batch->id,
            'status' => StatusEnum::PICKEDUP->value,
        ]);

        $stat = $this->service->getDownloadedVideoStats($user);

        $this->assertEquals('Heruntergeladene Videos', $stat->getLabel());
        $this->assertEquals('1', (string)$stat->getValue());
        $this->assertEquals('Videos heruntergeladen', $stat->getDescription());
        $this->assertEquals('primary', $stat->getColor());
    }

    public function testAvailableOffersStats(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $batch = Batch::factory()->create();

        // QUEUED oder NOTIFIED sind "ready" laut getReadyStatus()
        $readyStatus = StatusEnum::getReadyStatus()[0];

        $video = Video::factory()->create();
        Clip::factory()->create([
            'video_id' => $video->id,
            'user_id' => $user->id,
        ]);

        Assignment::factory()->create([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'batch_id' => $batch->id,
            'status' => $readyStatus,
            'expires_at' => now()->addDay(),
        ]);

        $stat = $this->service->getAvailableOffersStats($user);

        $this->assertEquals('Verfügbare Offers', $stat->getLabel());
        $this->assertEquals('1', (string)$stat->getValue());
        $this->assertEquals('bereit zum Versenden', $stat->getDescription());
        $this->assertEquals('success', $stat->getColor());
    }

    public function testExpiredOffersStats(): void
    {
        $user = User::factory()->create();
        $channel = Channel::factory()->create();
        $batch = Batch::factory()->create();

        $video = Video::factory()->create();
        Clip::factory()->create([
            'video_id' => $video->id,
            'user_id' => $user->id,
        ]);

        Assignment::factory()->create([
            'video_id' => $video->id,
            'channel_id' => $channel->id,
            'batch_id' => $batch->id,
            'status' => StatusEnum::EXPIRED->value,
        ]);

        $stat = $this->service->getExpiredOffersStats($user);

        $this->assertEquals('Abgelaufene Offers', $stat->getLabel());
        $this->assertEquals('1', (string)$stat->getValue());
        $this->assertEquals('nicht mehr aktiv', $stat->getDescription());
        $this->assertEquals('gray', $stat->getColor());
    }
}
