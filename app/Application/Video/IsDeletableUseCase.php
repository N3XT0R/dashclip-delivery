<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Enum\StatusEnum;
use App\Models\Video;

class IsDeletableUseCase
{

    public function handle(?Video $video): bool
    {
        if (!$video) {
            return false;
        }

        if ($video->hasAttribute('available_assignments_count')) {
            if ($video->getAttribute('available_assignments_count') > 0) {
                return false;
            }
        }

        if ($video->assignments()->where('status', StatusEnum::PICKEDUP->value)->count() > 0) {
            return false;
        }

        return true;
    }
}
