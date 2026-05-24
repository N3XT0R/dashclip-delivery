<?php

declare(strict_types=1);

namespace Tests\Integration\Jobs;

use App\Events\Video\VideoQueuedForIngest;
use App\Jobs\ProcessWebDavZipJob;
use App\Models\User;
use App\Services\Contracts\UnzipServiceInterface;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\DatabaseTestCase;
use Tests\Support\FakeUnzipService;

final class ProcessWebDavZipJobTest extends DatabaseTestCase
{
    public function testExtractsZipAndFiresIngestEvent(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Storage::fake('import');

        Storage::disk('import')->put('webdav/42/archive.zip', 'zip-content');
        Storage::disk('import')->put('webdav/42/video.mp4', 'video-content');

        $fakeUnzip = (new FakeUnzipService())->withExtractResult(true);
        $this->app->instance(UnzipServiceInterface::class, $fakeUnzip);

        $user = User::factory()->create();

        ProcessWebDavZipJob::dispatchSync(
            disk: 'import',
            path: 'webdav/42/archive.zip',
            userId: $user->id,
        );

        $this->assertSame(1, $fakeUnzip->extractSingleCallCount);
        Event::assertDispatched(VideoQueuedForIngest::class);
    }

    public function testSkipsIfZipExtractionFails(): void
    {
        Event::fake([VideoQueuedForIngest::class]);
        Storage::fake('import');
        Storage::disk('import')->put('webdav/42/broken.zip', 'bad-zip');

        $fakeUnzip = (new FakeUnzipService())->withExtractResult(false);
        $this->app->instance(UnzipServiceInterface::class, $fakeUnzip);

        ProcessWebDavZipJob::dispatchSync(
            disk: 'import',
            path: 'webdav/42/broken.zip',
            userId: null,
        );

        Event::assertNotDispatched(VideoQueuedForIngest::class);
    }
}
