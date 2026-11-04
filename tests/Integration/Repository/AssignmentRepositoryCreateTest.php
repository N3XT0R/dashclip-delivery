<?php

declare(strict_types=1);

namespace Tests\Integration\Repository;

use App\Exceptions\Video\VideoUnavailableForOfferException;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use Tests\DatabaseTestCase;

final class AssignmentRepositoryCreateTest extends DatabaseTestCase
{
    public function testCreatesTheAssignmentForAnAvailableVideo(): void
    {
        $video = Video::factory()->create();
        $channel = Channel::factory()->create();

        $assignment = app(AssignmentRepository::class)->createAssignment($video, $channel, Batch::factory()->create());

        $this->assertDatabaseHas('assignments', ['id' => $assignment->getKey(), 'video_id' => $video->getKey()]);
    }

    public function testRefusesAVideoThatWasDeletedMeanwhile(): void
    {
        $video = Video::factory()->create();
        Video::query()->whereKey($video->getKey())->first()->delete();

        $this->expectException(VideoUnavailableForOfferException::class);

        app(AssignmentRepository::class)->createAssignment($video, Channel::factory()->create(), Batch::factory()->create());
    }
}
