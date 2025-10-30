<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\FileInfoDto;
use App\Facades\DynamicStorage;
use App\Models\Clip;
use App\Models\Video;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

readonly class VideoService
{
    public function __construct(
        private PreviewService $previews
    ) {
    }

    public function isDuplicate(string $hash): bool
    {
        return Video::query()->where('hash', $hash)->exists();
    }

    /**
     * @param  string  $hash
     * @param  string  $ext
     * @param  int  $bytes
     * @param  string  $absolutePath
     * @param  string  $fileName
     * @return Video
     * @deprecated use createVideoBydDiskAndFileInfoDto instead
     */
    public function createLocal(string $hash, string $ext, int $bytes, string $absolutePath, string $fileName): Video
    {
        return Video::query()->create([
            'hash' => $hash,
            'ext' => $ext,
            'bytes' => $bytes,
            'path' => $this->makeStorageRelative($absolutePath),
            'disk' => 'local',
            'meta' => null,
            'original_name' => $fileName,
        ]);
    }

    public function createVideoBydDiskAndFileInfoDto(
        string $diskName,
        FileSystem $disk,
        FileInfoDto $file,
    ): Video {
        $hash = DynamicStorage::getHashForFileInfoDto($disk, $file);
        $pathToFile = $file->path;

        return Video::query()->create([
            'hash' => $hash,
            'ext' => $file->extension,
            'bytes' => $disk->size($pathToFile),
            'path' => $pathToFile,
            'disk' => $diskName,
            'meta' => null,
            'original_name' => $file->originalName ?? $file->basename,
        ]);
    }

    /**
     * @param  Video  $video
     * @param  string  $sourcePath
     * @param  OutputInterface|null  $output
     * @param  callable|null  $log
     * @return string|null
     * @deprecated use PreviewService::generatePreviewByDisk instead
     */
    public function generatePreview(
        Video $video,
        string $sourcePath,
        ?OutputInterface $output = null,
        ?callable $log = null
    ): ?string {
        try {
            $this->previews->setOutput($output);
            $clip = $video->clips()->first();

            if ($clip && $clip->start_sec !== null && $clip->end_sec !== null) {
                return $this->previews->generateForClip($clip);
            }

            return $this->previews->generate($video, 0, 10);
        } catch (Throwable $e) {
            Log::warning('Preview generation failed', [
                'file' => $sourcePath,
                'exception' => $e->getMessage(),
            ]);

            if ($log) {
                $log("Warnung: Preview konnte nicht erstellt werden ({$e->getMessage()})");
            }

            return null;
        }
    }


    /**
     * @param  string  $absolute
     * @return string
     * @deprecated use PathBuilder instead
     */
    private function makeStorageRelative(string $absolute): string
    {
        $root = rtrim(str_replace('\\', '/', storage_path('app')), '/');
        $absolute = str_replace('\\', '/', $absolute);

        if (str_starts_with($absolute, $root.'/')) {
            return substr($absolute, strlen($root) + 1);
        }

        $rootParts = explode('/', trim($root, '/'));
        $absParts = explode('/', trim($absolute, '/'));
        $i = 0;
        while (isset($rootParts[$i], $absParts[$i]) && $rootParts[$i] === $absParts[$i]) {
            $i++;
        }

        $relParts = array_fill(0, count($rootParts) - $i, '..');
        $relParts = array_merge($relParts, array_slice($absParts, $i));

        return implode('/', $relParts);
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