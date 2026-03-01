<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Log;
use Throwable;

class VideoObserver extends BaseObserver
{
    public function __construct(private readonly VideoService $videoService)
    {
    }

    public function deleting(Video|Model $model): bool
    {
        $path = $model->getAttribute('path');
        if (!$path) {
            return true;
        }

        try {
            $storageDisk = $model->getDisk();
            $targetDisk = config('preview.default_disk', 'public');
            $previewDisk = Storage::disk($targetDisk);
            $previewPath = $this->videoService->getPreviewPath($model);

            if ($storageDisk->exists($path) && !$storageDisk->delete($path)) {
                Log::warning('video delete failed', ['video_id' => $model->getKey(), 'path' => $path]);
                return false;
            }

            if (null !== $previewPath && $previewDisk->exists($previewPath) && !$previewDisk->delete($previewPath)) {
                Log::warning('preview delete failed', ['video_id' => $model->getKey(), 'path' => $previewPath]);
                return false;
            }
        } catch (Throwable $e) {
            Log::error('File delete threw',
                ['video_id' => $model->getKey(), 'path' => $path, 'err' => $e->getMessage(), 'exception' => $e]);
            return false;
        }

        return true;
    }
}