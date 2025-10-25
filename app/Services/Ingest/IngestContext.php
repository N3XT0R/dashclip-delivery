<?php

declare(strict_types=1);

namespace App\Services\Ingest;

use App\DTO\FileInfoDto;
use App\Enum\Ingest\IngestResult;
use App\Models\Batch;
use App\Models\Video;
use App\ValueObjects\IngestStats;
use Illuminate\Contracts\Filesystem\Filesystem;
use Symfony\Component\Console\Style\OutputStyle;

class IngestContext
{
    public ?OutputStyle $output = null;
    public ?string $finalPath = null;
    public ?IngestStats $stats = null;
    public ?Batch $batch = null;
    public ?IngestResult $result = null;

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

