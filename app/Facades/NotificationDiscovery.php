<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\Notifications\NotificationDiscoveryService;
use Illuminate\Support\Facades\Facade;

class NotificationDiscovery extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationDiscoveryService::class;
    }
}