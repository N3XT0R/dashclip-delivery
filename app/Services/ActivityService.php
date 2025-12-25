<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class ActivityService
{
    public function createActivityLog(
        string $logName,
        User $user,
        string $activityType,
        Collection|array $details = []
    ): void {
        activity($logName)
            ->causedBy($user)
            ->withProperties($details)
            ->log($activityType);
    }
}
