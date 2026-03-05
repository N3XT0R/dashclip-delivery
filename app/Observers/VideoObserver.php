<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Video;
use App\Services\VideoService;
use Illuminate\Database\Eloquent\Model;
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
            if ($storageDisk->exists($path) && !$storageDisk->delete($path)) {
                Log::warning('video delete failed', ['video_id' => $model->getKey(), 'path' => $path]);
                return false;
            }
        } catch (Throwable $e) {
            Log::error(
                'File delete threw',
                ['video_id' => $model->getKey(), 'path' => $path, 'err' => $e->getMessage(), 'exception' => $e]
            );
            return false;
        }

        return true;
    }
}
