<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Enum\StatusEnum;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;

class IsDeletableUseCase
{
    /**
     * A video may be deleted only while it has no active (ready, non-expired)
     * offers and no offer that was already picked up. Mirrors the visibility
     * rule of the Standard-panel delete action.
     *
     * When the caller eager-loaded `available_assignments_count` (as the
     * Filament list query does) that value is trusted; otherwise the count is
     * computed here so plain model instances are handled correctly too.
     */
    public function handle(?Video $video): bool
    {
        if (!$video) {
            return false;
        }

        if ($this->availableAssignmentsCount($video) > 0) {
            return false;
        }

        return $video->assignments()
            ->where('status', StatusEnum::PICKEDUP->value)
            ->doesntExist();
    }

    private function availableAssignmentsCount(Video $video): int
    {
        if ($video->hasAttribute('available_assignments_count')) {
            return (int)$video->getAttribute('available_assignments_count');
        }

        return $video->assignments()
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->where(static function (Builder $query): void {
                $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })
            ->count();
    }
}
