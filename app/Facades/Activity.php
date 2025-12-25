<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\ActivityService;
use Illuminate\Support\Facades\Facade;

/**
 * @method static void createActivityLog(string $logName, \App\Models\User $user, string $activityType, \Illuminate\Support\Collection|array $details = [])
 */
class Activity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityService::class;
    }
}
