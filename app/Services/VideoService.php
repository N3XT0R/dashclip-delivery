<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Video;

class VideoService
{
    public function isDuplicate(string $hash): bool
    {
        return Video::query()->where('hash', $hash)->exists();
    }
}