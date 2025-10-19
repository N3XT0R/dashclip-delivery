<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

class DynamicStorageService
{
    public function fromPath(string $path): Filesystem
    {
        $root = is_dir($path) ? $path : dirname($path);
        $this->assertDirectory($root);

        return Storage::build([
            'driver' => 'local',
            'root' => $root,
        ]);
    }

    private function assertDirectory(string $path): void
    {
        if (!is_dir($path)) {
            throw new \RuntimeException("Inbox fehlt: {$path}");
        }
    }

}