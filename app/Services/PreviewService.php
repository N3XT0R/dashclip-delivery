<?php

declare(strict_types=1);

namespace App\Services;

use App\Facades\Cfg;
use App\Facades\DynamicStorage;
use App\Models\Clip;
use App\Models\Video;
use App\Support\PathBuilder;
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

    public function generatePreviewByDisk(
        Filesystem $disk,
        string $relativePath,
        ?int $startSec = 0,
        ?int $endSec = null
    ): ?string {
        if (!$this->isValidRange($startSec, $endSec)) {
            $this->warn("Invalid time range: start={$startSec}, end={$endSec}");
            return null;
        }

        $absoluteSource = $disk->path($relativePath);
        $hash = DynamicStorage::getHashForFilePath($disk, $relativePath);
        $duration = $endSec - $startSec;

        $previewDisk = Storage::disk(config('preview.default_disk', 'public'));
        $previewPath = PathBuilder::forPreview($hash);

        if ($previewDisk->exists($previewPath)) {
            $this->info("Preview exists in cache: {$previewPath}");
            return $previewDisk->url($previewPath);
        }
    }

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
        $previewPath = $this->buildPath($video, $start, $end);

        return $previewDisk->exists($previewPath) ? $previewDisk->url($previewPath) : null;
    }

    // ───────────────────────── internal / helpers ─────────────────────────

    private function ffmpegParams(): array
    {
        $crf = (int)Cfg::get('ffmpeg_crf', 'ffmpeg', 28);
        $preset = (string)Cfg::get('ffmpeg_preset', 'ffmpeg', 'veryfast');
        $extra = (array)Cfg::get('ffmpeg_video_args', 'ffmpeg', []);

        return array_merge(['-preset', $preset, '-crf', (string)$crf], $extra);
    }

    private function isValidRange(int $start, int $end): bool
    {
        return $start >= 0 && $end > $start;
    }

    private function normalizeRelative(string $path): string
    {
        // Filesystem adapters expect relative paths (root is prefixed by the adapter)
        return ltrim($path, '/');
    }

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

