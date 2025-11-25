<?php

declare(strict_types=1);

namespace App\DTO;

use Illuminate\Support\Collection;

class UploaderPoolInfo
{
    public function __construct(
        public string $type,
        public string|int $id,
        public Collection $videos,
    ) {
    }
}
