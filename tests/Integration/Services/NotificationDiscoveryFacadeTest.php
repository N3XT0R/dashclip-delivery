<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Facades\NotificationDiscovery;
use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;
use App\Providers\ServiceServiceProvider;
use App\Services\Notifications\Decorator\CachedNotificationDiscoveryService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

final class NotificationDiscoveryFacadeTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function getPackageProviders($app): array
    {
        return [
            ServiceServiceProvider::class,
        ];
    }

    public function testFacadeListsNotificationsViaCachedService(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('notification_discovery', 3600, Mockery::type('Closure'))
            ->andReturnUsing(function (string $key, int $ttl, callable $callback) {
                return $callback();
            });

        $result = NotificationDiscovery::list();

        $this->assertContains(UserUploadDuplicatedNotification::class, $result);
        $this->assertContains(UserUploadProceedNotification::class, $result);
    }

    public function testFacadeResolvesCachedDiscoveryService(): void
    {
        $service = NotificationDiscovery::getFacadeRoot();

        $this->assertInstanceOf(CachedNotificationDiscoveryService::class, $service);
    }
}
