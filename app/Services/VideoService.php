<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\FileInfoDto;
use App\Facades\DynamicStorage;
use App\Models\Clip;
use App\Models\Video;
use App\Repository\VideoRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

readonly class VideoService
{

    public function __construct(private VideoRepository $videoRepository)
    {
    }

    public function isDuplicate(string $hash): bool
    {
        return Video::query()->where('hash', $hash)->exists();
    }

    public function createVideoBydDiskAndFileInfoDto(
        string $diskName,
        FileSystem $disk,
        FileInfoDto $file,
    ): Video {
        $hash = DynamicStorage::getHashForFileInfoDto($disk, $file);
        $pathToFile = $file->path;
        $video = $this->videoRepository->firstOrCreate([
            'hash' => $hash,
            'ext' => $file->extension,
            'bytes' => $disk->size($pathToFile),
            'path' => $pathToFile,
            'disk' => $diskName,
            'meta' => null,
            'original_name' => $file->originalName ?? $file->basename,
        ]);

        if ($video->wasRecentlyCreated) {
            Log::debug('Neues Video angelegt', ['hash' => $video->hash]);
        } else {
            Log::debug('Video existierte bereits', ['hash' => $video->hash]);
        }

        return $video;
    }

    public function finalizeUpload(Video $video, string $dstRel, string $diskName, ?string $previewUrl): void
    {
        $video->update([
            'path' => $dstRel,
            'disk' => $diskName,
            'preview_url' => $previewUrl,
        ]);
    }

    public function createClipForVideo(Video $video, int $startSec, int $endSec): Model&Clip
    {
        return $video->clips()->create([
            'start_sec' => $startSec,
            'end_sec' => $endSec,
        ]);
    }

    public function getClipForVideo(Video $video, int $startSec, int $endSec): ?Clip
    {
        return $video->clips()->where('start_sec', $startSec)->where('end_sec', $endSec)->first();
    }
}