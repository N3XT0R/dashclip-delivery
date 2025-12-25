<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\ActivityService;
use Illuminate\Support\Facades\Facade;

class Activity extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return ActivityService::class;
    }
}
