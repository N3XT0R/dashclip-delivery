<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;
use App\Providers\ServiceServiceProvider;
use App\Services\Notifications\Decorator\CachedNotificationDiscoveryService;
use App\Services\Notifications\NotificationDiscoveryService;
use Illuminate\Support\Facades\Cache;
use Mockery;
use Tests\TestCase;

final class NotificationDiscoveryServiceTest extends TestCase
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

    public function testListsUserFacingNotifications(): void
    {
        $service = app(NotificationDiscoveryService::class, ['useCache' => false]);

        $result = $service->list();

        $this->assertContains(UserUploadDuplicatedNotification::class, $result);
        $this->assertContains(UserUploadProceedNotification::class, $result);
    }

    public function testBindingAllowsDisablingCache(): void
    {
        $service = app(NotificationDiscoveryService::class, ['useCache' => false]);

        $this->assertInstanceOf(NotificationDiscoveryService::class, $service);
        $this->assertNotInstanceOf(CachedNotificationDiscoveryService::class, $service);
    }

    public function testCachedDecoratorUsesConfiguredTtl(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->with('notification_discovery', 600, Mockery::type('Closure'))
            ->andReturn(['cached']);

        $service = app(NotificationDiscoveryService::class, [
            'ttl' => 600,
            'useCache' => true,
        ]);

        $this->assertInstanceOf(CachedNotificationDiscoveryService::class, $service);
        $this->assertSame(['cached'], $service->list());
    }
}
