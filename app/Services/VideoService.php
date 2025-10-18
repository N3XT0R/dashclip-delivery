<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Video;
use Illuminate\Support\Facades\Log;
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

    public function generatePreview(Video $video, string $sourcePath, ?callable $log = null): ?string
    {
        try {
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
}