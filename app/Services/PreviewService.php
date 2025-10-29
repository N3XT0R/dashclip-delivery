<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidTimeRangeException;
use App\Exceptions\PreviewGenerationException;
use App\Facades\Cfg;
use App\Facades\DynamicStorage;
use App\Facades\PathBuilder;
use App\Models\Clip;
use App\Models\Video;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Filters\Video\VideoFilters;
use FFMpeg\Format\Video\X264;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use ProtoneMedia\LaravelFFMpeg\Support\FFMpeg;
use Throwable;

final class PreviewService
{
    private ?OutputStyle $output = null;

    // ───────────────────────── public API ─────────────────────────

    public function setOutput(?OutputStyle $outputStyle = null): void
    {
        $this->output = $outputStyle;
    }

    /**
     * Generate a video preview for the given clip.
     * @param  Clip  $clip
     * @return string|null
     * @deprecated use generatePreviewByDisk instead
     */
    public function generateForClip(Clip $clip): ?string
    {
        $video = $clip->video;
        if (!$video) {
            $this->warn('Clip has no associated video.');

            return null;
        }

        $start = $clip->start_sec;
        $end = $clip->end_sec;

        if ($start === null || $end === null) {
            $this->warn("Clip {$clip->getKey()} has no valid time range.");

            return null;
        }

        return $this->generate($video, $start, $end);
    }

    /**
     * Generate a video preview for the given time range.
     * @param  Filesystem  $disk
     * @param  string  $relativePath
     * @param  int|null  $id
     * @param  int|null  $startSec
     * @param  int|null  $endSec
     * @return string
     */
    public function generatePreviewByDisk(
        Filesystem $disk,
        string $relativePath,
        ?int $id,
        ?int $startSec = 0,
        ?int $endSec = null
    ): string {
        if ($endSec !== null && !$this->isValidRange($startSec, $endSec)) {
            throw new InvalidTimeRangeException($startSec, $endSec);
        }

        $targetDisk = config('preview.default_disk', 'public');
        $duration = $endSec !== null ? $endSec - $startSec : null;

        $previewDisk = Storage::disk($targetDisk);

        if ($id && $endSec) {
            $previewPath = PathBuilder::forPreview($id, $startSec, $endSec);
        } else {
            $fileHash = DynamicStorage::getHashForFilePath($disk, $relativePath);
            $previewPath = PathBuilder::forPreviewByHash($fileHash);
        }


        if ($previewDisk->exists($previewPath)) {
            $this->info("Preview exists in cache: {$previewPath}");
            return $previewDisk->url($previewPath);
        }

        if ($bin = Cfg::get('ffmpeg_bin', 'ffmpeg', null)) {
            config(['laravel-ffmpeg.ffmpeg.binaries' => $bin]);
        }

        try {
            $audioCodec = (string)Cfg::get('ffmpeg_audio_codec', 'ffmpeg', 'aac');
            $videoCodec = (string)Cfg::get('ffmpeg_video_codec', 'ffmpeg', 'libx264');
            $format = new X264($audioCodec, $videoCodec);

            $params = $this->ffmpegParams();
            if ($params !== []) {
                $format->setAdditionalParameters($params);
            }

            FFMpeg::fromFilesystem($disk)
                ->open($relativePath)
                ->addFilter(function (VideoFilters $filters) use ($startSec, $duration): void {
                    if ($duration === null) {
                        $filters->clip(TimeCode::fromSeconds($startSec));
                    } else {
                        $filters->clip(TimeCode::fromSeconds($startSec), TimeCode::fromSeconds($duration));
                    }
                })
                ->export()
                ->toDisk($targetDisk)
                ->inFormat($format)
                ->save($previewPath);

            return $previewDisk->url($previewPath);
        } catch (Throwable $e) {
            $this->error('ffmpeg failed: '.$e->getMessage());
            throw PreviewGenerationException::fromDisk(
                $relativePath,
                $disk->path($relativePath),
                $e
            );
        }
    }

    /**
     * Generate a video preview for the given time range.
     * @param  Video  $video
     * @param  int  $start
     * @param  int  $end
     * @return string|null
     * @deprecated use generatePreviewByDisk instead
     */
    public function generate(Video $video, int $start, int $end): ?string
    {
        if (!$this->isValidRange($start, $end)) {
            $this->warn("Invalid time range: start={$start}, end={$end}");

            return null;
        }

        $duration = $end - $start;
        /**
         * @var string $sourceDisk
         */
        $sourceDisk = $video->disk ?? 'local';
        $relPath = $this->normalizeRelative($video->path);

        // Check target (cache)
        $previewDisk = Storage::disk('public');
        $previewPath = $this->buildPath($video, $start, $end);

        if ($previewDisk->exists($previewPath)) {
            $this->info("Preview exists in cache: {$previewPath}");
            return $previewDisk->url($previewPath);
        }

        // Ensure destination directory exists (especially for fake disks)
        $previewDisk->makeDirectory(dirname($previewPath));
        Log::error(dirname($previewPath));

        // Configure FFMpeg binary
        if ($bin = Cfg::get('ffmpeg_bin', 'ffmpeg', null)) {
            config(['laravel-ffmpeg.ffmpeg.binaries' => $bin]);
            //config(['laravel-ffmpeg.ffprobe.binaries' => $bin]);
        }

        try {
            $audioCodec = (string)Cfg::get('ffmpeg_audio_codec', 'ffmpeg', 'aac');
            $videoCodec = (string)Cfg::get('ffmpeg_video_codec', 'ffmpeg', 'libx264');
            $format = new X264($audioCodec, $videoCodec);
            $params = $this->ffmpegParams();
            if ($params !== []) {
                $format->setAdditionalParameters($params);
            }

            Log::error($sourceDisk);
            Log::error($relPath);
            Log::error($previewPath);

            FFMpeg::fromDisk($sourceDisk)
                ->open($relPath)
                ->addFilter(function (VideoFilters $filters) use ($start, $duration): void {
                    $filters->clip(TimeCode::fromSeconds($start), TimeCode::fromSeconds($duration));
                })
                ->export()
                ->toDisk('public')
                ->inFormat($format)
                ->save($previewPath);

            if (!$previewDisk->exists($previewPath)) {
                $this->error('ffmpeg failed: output missing');

                return null;
            }

            try {
                $size = $previewDisk->size($previewPath);
            } catch (Throwable $e) {
                $size = 0;
            }
            $this->info("Preview created: {$previewPath} (".Number::fileSize($size).')');

            return $previewDisk->url($previewPath);
        } catch (Throwable $e) {
            $message = 'ffmpeg failed: '.$e->getMessage();
            $this->error($message);
            Log::error($message, ['exception' => $e]);

            return null;
        }
    }

    public function url(Video $video, int $start, int $end): ?string
    {
        if (!$this->isValidRange($start, $end)) {
            return null;
        }

        $previewDisk = Storage::disk('public');
        $previewPath = PathBuilder::forPreview($video->getKey(), $start, $end);

        return $previewDisk->exists($previewPath) ? $previewDisk->url($previewPath) : null;
    }

    // ───────────────────────── internal / helpers ─────────────────────────

    /**
     * Get ffmpeg parameters from config.
     * @return array
     */
    private function ffmpegParams(): array
    {
        $crf = (int)Cfg::get('ffmpeg_crf', 'ffmpeg', 28);
        $preset = (string)Cfg::get('ffmpeg_preset', 'ffmpeg', 'veryfast');
        $extra = (array)Cfg::get('ffmpeg_video_args', 'ffmpeg', []);

        return array_merge(['-preset', $preset, '-crf', (string)$crf], $extra);
    }

    /**
     * Check if the given time range is valid.
     * @param  int  $start
     * @param  int  $end
     * @return bool
     */
    private function isValidRange(int $start, int $end): bool
    {
        return $start >= 0 && $end > $start;
    }

    /**
     * Normalize a path to be relative (no leading slash).
     * @param  string  $path
     * @return string
     * @deprecated no replacement
     */
    private function normalizeRelative(string $path): string
    {
        // Filesystem adapters expect relative paths (root is prefixed by the adapter)
        return ltrim($path, '/');
    }

    /**
     * Build the preview path for the given video and time range.
     * @param  Video  $video
     * @param  int  $start
     * @param  int  $end
     * @return string
     * @deprecated use PathBuilder instead
     */
    private function buildPath(Video $video, int $start, int $end): string
    {
        $hash = md5($video->getKey().'_'.$start.'_'.$end);

        return "previews/{$hash}.mp4";
    }

    // ───────────────────────── logging helpers ─────────────────────────

    private function info(string $message): void
    {
        $this->output?->writeln("<info>{$message}</info>");
        Log::info($message, ['service' => 'PreviewService']);
    }

    private function warn(string $message): void
    {
        $this->output?->writeln("<comment>{$message}</comment>");
        Log::warning($message, ['service' => 'PreviewService']);
    }

    private function error(string $message): void
    {
        $this->output?->writeln("<error>{$message}</error>");
        Log::error($message, ['service' => 'PreviewService']);
    }
}

