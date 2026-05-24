<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTO\FileInfoDto;
use App\Events\Video\VideoQueuedForIngest;
use App\Models\User;
use App\Services\Contracts\UnzipServiceInterface;
use App\Services\VideoService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class ProcessWebDavZipJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public readonly string $disk,
        public readonly string $path,
        public readonly int|string|null $userId,
    ) {
    }

    /**
     * Extract the uploaded ZIP archive and queue each extracted video file for ingest.
     *
     * @param UnzipServiceInterface $unzip
     * @param VideoService $videoService
     */
    public function handle(UnzipServiceInterface $unzip, VideoService $videoService): void
    {
        $storage = Storage::disk($this->disk);
        $absoluteZip = $storage->path($this->path);
        $absoluteDir = dirname($absoluteZip);

        $ok = $unzip->extractSingle($absoluteZip, $absoluteDir);

        if (!$ok) {
            Log::warning('WebDAV ZIP extraction failed', ['path' => $this->path, 'disk' => $this->disk]);
            $this->fail();
            return;
        }

        $user = $this->userId ? User::find($this->userId) : null;
        $userDir = dirname($this->path);

        foreach ($storage->files($userDir) as $relativePath) {
            if (str_ends_with(strtolower($relativePath), '.zip')) {
                continue;
            }

            $fileInfo = FileInfoDto::fromPath($relativePath);
            $video = $videoService->createVideoBydDiskAndFileInfoDto($this->disk, $storage, $fileInfo);

            if ($video->wasRecentlyCreated) {
                VideoQueuedForIngest::dispatch($video, $user);
            }
        }
    }
}
