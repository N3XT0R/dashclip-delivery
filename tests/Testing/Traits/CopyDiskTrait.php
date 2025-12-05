<?php

declare(strict_types=1);

namespace Tests\Testing\Traits;

use Illuminate\Contracts\Filesystem\Filesystem;

trait CopyDiskTrait
{
    /**
     * Recursively copies all files from one Laravel disk to another.
     */
    public function copyDisk(Filesystem $source, Filesystem $target): void
    {
        foreach ($source->allFiles() as $path) {
            $target->put($path, $source->get($path));
        }

        // Optional: copy empty directories as well
        foreach ($source->allDirectories() as $dir) {
            $target->makeDirectory($dir);
        }
    }
}