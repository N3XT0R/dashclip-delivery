<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\FileInfoDto;
use App\Enum\ProcessingStatusEnum;
use App\Exceptions\Video\VideoFileRemovalException;
use App\Facades\DynamicStorage;
use App\Models\Clip;
use App\Models\Video;
use App\Repository\VideoRepository;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Throwable;

readonly class VideoService
{
    public function __construct(private VideoRepository $videoRepository)
    {
    }

    public function isDuplicate(string $hash): bool
    {
        return $this->videoRepository->isDuplicate($hash);
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

    public function createClipForVideo(Video $video, int $startSec, int $endSec): Model&Clip
    {
        return $video->clips()->create([
            'start_sec' => $startSec,
            'end_sec' => $endSec,
        ]);
    }

    public function getClipForVideo(Video $video, int $startSec, int $endSec): ?Clip
    {
        return $this->videoRepository->getClipForVideo($video, $startSec, $endSec);
    }

    public function assignVideoToOwnTeam(Video $video): bool
    {
        $result = false;
        $firstClip = $video->clips()->first();
        if ($firstClip !== null) {
            $user = $firstClip->user;
            $team = $user->ownTeams->first();
            if ($team !== null) {
                $result = $this->videoRepository->update($video, [
                    'team_id' => $team->getKey()
                ]);
            }
        }

        return $result;
    }

    /**
     * Deletes a video that was identified as a duplicate.
     * Removes both stored versions and soft-deletes the video record.
     * @throws VideoFileRemovalException when an existing file cannot be removed
     * @param Video $video
     * @return bool
     */
    public function deleteDuplicateVideo(Video $video): bool
    {
        $this->removeVideoFiles($video);

        $user = $this->videoRepository->getUploaderUser($video);
        if (null === $user) {
            $user = $video->team()->first()?->owner;
        }

        if ($user) {
            app(NotificationService::class)->notifyDuplicatedUpload($user, $video);
        }

        return $video->delete();
    }

    /**
     * Finds videos whose files are missing from storage, based on their processing status.
     * @param ProcessingStatusEnum $processingStatusEnum
     * @return LazyCollection
     */
    public function findVideosMissingFromStorage(
        ProcessingStatusEnum $processingStatusEnum = ProcessingStatusEnum::Completed,
        int $chunkSize = 1000,
    ): LazyCollection {
        return $this->videoRepository
            ->getLazyAllByProcessingStatus(
                status: $processingStatusEnum,
                chunkSize: $chunkSize
            )
            ->reject(function (Video $video) {
                return $video->getDisk()->exists($video->path);
            });
    }

    /**
     * Removes both stored versions and soft-deletes the video.
     * @throws VideoFileRemovalException when an existing file cannot be removed
     * @param Video $video
     * @return bool
     */
    public function delete(Video $video): bool
    {
        if ($video->exists) {
            $this->removeVideoFiles($video);
        }

        return $video->delete();
    }

    /**
     * Remove both stored video versions and all clip previews; the database rows stay.
     * @param Video $video
     * @return void
     * @throws VideoFileRemovalException when a file exists but cannot be removed
     */
    public function removeStoredFiles(Video $video): void
    {
        try {
            $this->removeVideoFiles($video);

            foreach ($video->clipsWithTrashed()->get() as $clip) {
                $previewPath = $clip->getAttribute('preview_path');
                if (!$previewPath || !$clip->getAttribute('preview_disk')) {
                    continue;
                }

                $previewDisk = $clip->getDisk();
                if ($previewDisk->exists($previewPath) && !$previewDisk->delete($previewPath)) {
                    throw new VideoFileRemovalException('A clip preview could not be removed.');
                }
            }
        } catch (VideoFileRemovalException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new VideoFileRemovalException($exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Remove both video versions, allowing a retry after partially completed cleanup.
     * @throws VideoFileRemovalException when an existing file cannot be removed
     */
    private function removeVideoFiles(Video $video): void
    {
        try {
            $disk = $video->getDisk();
            foreach ($video->storedFilePaths() as $path) {
                if ($disk->exists($path) && !$disk->delete($path)) {
                    throw new VideoFileRemovalException('A video file could not be removed.');
                }
            }
        } catch (VideoFileRemovalException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new VideoFileRemovalException($exception->getMessage(), previous: $exception);
        }
    }
}
