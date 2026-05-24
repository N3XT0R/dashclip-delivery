<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners\WebDav;

use App\Jobs\ProcessWebDavZipJob;
use App\Listeners\WebDav\ZipUploadedListener;
use Illuminate\Support\Facades\Queue;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileCreatedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileUpdatedEvent;
use N3XT0R\LaravelWebdavServer\ValueObjects\WebDavPrincipalValueObject;
use Tests\TestCase;

final class ZipUploadedListenerTest extends TestCase
{
    public function testDispatchesJobForZipFile(): void
    {
        Queue::fake();

        $principal = new WebDavPrincipalValueObject('user-42', 'John', null);
        $event = new FileCreatedEvent('import', 'webdav/user-42/archive.zip', $principal, 1024);

        app(ZipUploadedListener::class)->handle($event);

        Queue::assertPushed(ProcessWebDavZipJob::class, function ($job) {
            return $job->disk === 'import'
                && $job->path === 'webdav/user-42/archive.zip';
        });
    }

    public function testIgnoresNonZipFile(): void
    {
        Queue::fake();

        $principal = new WebDavPrincipalValueObject('user-42', 'John', null);
        $event = new FileCreatedEvent('import', 'webdav/user-42/video.mp4', $principal, 1024);

        app(ZipUploadedListener::class)->handle($event);

        Queue::assertNothingPushed();
    }

    public function testDispatchesJobForFileUpdatedEvent(): void
    {
        Queue::fake();

        $principal = new WebDavPrincipalValueObject('user-42', 'John', null);
        $event = new FileUpdatedEvent('import', 'webdav/user-42/archive.zip', $principal, 2048);

        app(ZipUploadedListener::class)->handle($event);

        Queue::assertPushed(ProcessWebDavZipJob::class, function ($job) {
            return $job->disk === 'import'
                && $job->path === 'webdav/user-42/archive.zip';
        });
    }
}
