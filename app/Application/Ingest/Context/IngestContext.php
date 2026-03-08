<?php

declare(strict_types=1);

namespace App\Application\Ingest\Context;

use App\Models\Clip;
use App\Models\Video;

final class IngestContext
{
    public function __construct(
        public Video $video,
        public ?Clip $clip = null,
        public ?string $hash = null,
        public bool $isDuplicate = false,
    ) {
    }
}
