<?php
declare(strict_types=1);

namespace Tests\Unit\Commands;

use App\Jobs\ProcessWebDavZipJob;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class IngestWebDavCommandTest extends TestCase
{
    public function testQueuesZipFilesFoundInWebDavDirectories(): void
    {
        Queue::fake();
        Storage::fake('import');
        Storage::disk('import')->put('webdav/1/archive.zip', 'content');
        Storage::disk('import')->put('webdav/1/video.mp4', 'content');
        Storage::disk('import')->put('webdav/2/batch.zip', 'content');

        $this->artisan('ingest:webdav')->assertExitCode(0);

        Queue::assertPushed(ProcessWebDavZipJob::class, 2);
        Queue::assertPushed(ProcessWebDavZipJob::class, fn($job) => $job->userId === null);
    }

    public function testDoesNothingWhenNoZipsFound(): void
    {
        Queue::fake();
        Storage::fake('import');
        Storage::disk('import')->put('webdav/1/video.mp4', 'content');

        $this->artisan('ingest:webdav')->assertExitCode(0);

        Queue::assertNothingPushed();
    }
}
