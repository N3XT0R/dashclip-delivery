<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidTimeRangeException;
use App\Exceptions\PreviewGenerationException;
use App\Facades\Cfg;
use App\Facades\DynamicStorage;
use App\Facades\PathBuilder;
use FFMpeg\Coordinate\TimeCode;
use FFMpeg\Filters\Video\VideoFilters;
use FFMpeg\Format\Video\X264;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
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

    // ───────────────────────── logging helpers ─────────────────────────

    private function info(string $message): void
    {
        $this->output?->writeln("<info>{$message}</info>");
        Log::info($message, ['service' => 'PreviewService']);
    }

    private function error(string $message): void
    {
        $this->output?->writeln("<error>{$message}</error>");
        Log::error($message, ['service' => 'PreviewService']);
    }
}

