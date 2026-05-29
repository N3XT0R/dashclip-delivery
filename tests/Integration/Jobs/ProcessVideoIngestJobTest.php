<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\Enum\ProcessingStatusEnum;
use App\Jobs\ProcessVideoIngestJob;
use App\Models\Video;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;

final class ProcessVideoIngestJobTest extends DatabaseTestCase
{
    public function testSkipsJobIfVideoNotFound(): void
    {
        ProcessVideoIngestJob::dispatchSync(videoId: 999999);

        $this->assertTrue(true);
    }

    public function testSkipsJobIfVideoIsDeleted(): void
    {
        $video = Video::factory()->create(['processing_status' => ProcessingStatusEnum::Deleted]);

        ProcessVideoIngestJob::dispatchSync(videoId: $video->id);

        $this->assertSame(ProcessingStatusEnum::Deleted, $video->refresh()->processing_status);
    }

    public function testSkipsJobIfVideoIsCompleted(): void
    {
        $video = Video::factory()->create(['processing_status' => ProcessingStatusEnum::Completed]);

        ProcessVideoIngestJob::dispatchSync(videoId: $video->id);

        $this->assertSame(ProcessingStatusEnum::Completed, $video->refresh()->processing_status);
    }

    public function testRunsPipelineForPendingVideo(): void
    {
        \App\Models\Config::where('key', \App\Constants\Config\DefaultConfigEntry::DEFAULT_FILE_SYSTEM)
            ->update(['value' => 'local']);

        Storage::fake('local');
        Storage::disk('local')->put('videos/test.mp4', 'video-content');

        $video = Video::factory()->create([
            'disk' => 'local',
            'path' => 'videos/test.mp4',
            'hash' => null,
            'processing_status' => ProcessingStatusEnum::Pending,
        ]);

        ProcessVideoIngestJob::dispatchSync(videoId: $video->id);

        $this->assertSame(ProcessingStatusEnum::Completed, $video->refresh()->processing_status);
    }
}