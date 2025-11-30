<?php

declare(strict_types=1);

namespace Tests\Integration\Models;

use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Video;
use Tests\DatabaseTestCase;

final class BatchTest extends DatabaseTestCase
{
    public function testHasAssignmentsClipsAndChannels(): void
    {
        $batch = Batch::factory()->create();
        $video = Video::factory()->create();
        $channel = Channel::factory()->create();

        $assignment = Assignment::factory()
            ->forVideo($video)
            ->forChannel($channel)
            ->withBatch($batch)
            ->create();

        Clip::factory()->create([
            'video_id' => $assignment->video_id,
        ]);

        $batch->refresh();

        $this->assertCount(1, $batch->assignments);
        $this->assertTrue($batch->assignments->first()->is($assignment));
        $this->assertCount(1, $batch->clips);
        $this->assertCount(1, $batch->channels);
        $this->assertTrue($batch->channels->first()->is($channel));
    }
}
