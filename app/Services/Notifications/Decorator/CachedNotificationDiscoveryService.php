<?php

declare(strict_types=1);

namespace App\Services\Notifications\Decorator;

use App\Services\Notifications\NotificationDiscoveryService;
use Illuminate\Support\Facades\Cache;

class CachedNotificationDiscoveryService
{
    public function __construct(
        private readonly NotificationDiscoveryService $inner,
        private readonly int $ttl = 3600,
    ) {
    }

    public function list(): array
    {
        return Cache::remember('notification_discovery', $this->ttl, function () {
            return $this->inner->list();
        });
    }
}