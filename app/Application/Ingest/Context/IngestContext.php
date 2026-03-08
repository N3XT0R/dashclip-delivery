<?php

declare(strict_types=1);

namespace App\Application\Ingest\Context;

use App\Models\Video;
use Illuminate\Support\Collection;

final class IngestContext
{
    public function __construct(
        public Video $video,
        public Collection $clips,
        public ?string $hash = null,
        public bool $isDuplicate = false,
    ) {
    }
}
