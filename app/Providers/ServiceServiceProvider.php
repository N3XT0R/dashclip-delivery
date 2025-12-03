<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Notifications\Decorator\CachedNotificationDiscoveryService;
use App\Services\Notifications\NotificationDiscoveryService;
use Carbon\Laravel\ServiceProvider;

class ServiceServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->registerNotifications();
    }

    protected function registerNotifications(): void
    {
        $this->app->bind(
            NotificationDiscoveryService::class,
            function ($app, array $params) {
                $useCache = $params['useCache'] ?? true;

                $ttl = $params['ttl'] ?? 3600;

                $base = new NotificationDiscoveryService();

                if (!$useCache) {
                    return $base;
                }

                return new CachedNotificationDiscoveryService(
                    inner: $base,
                    ttl: $ttl,
                );
            }
        );
    }
}