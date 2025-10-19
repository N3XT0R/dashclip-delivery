<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class DynamicStorageService
{
    public function fromPath(string $path): Filesystem
    {
        return Storage::build([
            'driver' => 'local',
            'root' => is_dir($path) ? $path : dirname($path),
        ]);
    }
}