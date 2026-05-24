<?php

declare(strict_types=1);

namespace App\Listeners\WebDav;

use App\Jobs\ProcessWebDavZipJob;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileCreatedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileUpdatedEvent;

final class ZipUploadedListener
{
    public function handle(FileCreatedEvent|FileUpdatedEvent $event): void
    {
        if (!str_ends_with(strtolower($event->path), '.zip')) {
            return;
        }

        ProcessWebDavZipJob::dispatch(
            disk: $event->disk,
            path: $event->path,
            userId: $event->principal->user?->getKey(),
        );
    }
}
