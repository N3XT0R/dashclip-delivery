<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\DTO\FileInfoDto;
use App\Models\Video;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Console\Style\OutputStyle;

class IngestContext
{
    public ?OutputStyle $output = null;
    public ?string $finalPath = null;

    public function __construct(
        public Filesystem $disk,
        public FileInfoDto $file,
        public string $targetDisk,
        public ?Video $video = null,
        public ?string $hash = null,
        public ?string $previewUrl = null,
        public ?object $clip = null,
        public ?int $startSec = 0,
        public ?int $endSec = null,
    ) {
    }
}

