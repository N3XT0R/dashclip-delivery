<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use BezhanSalleh\FilamentShield\Facades\FilamentShield;
use App\Filament\Standard\Pages\ChannelApplication;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Mockery;
use ReflectionProperty;
use Tests\TestCase;

final class ChannelApplicationTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        restore_error_handler();
        restore_exception_handler();

        $property = new ReflectionProperty(ChannelApplication::class, 'pagePermissionKey');
        $property->setAccessible(true);
        $property->setValue(null, null);

        parent::tearDown();
    }

    public function testCanAccessReturnsFalseWhenShieldDenies(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('can')->andReturnFalse();
        $user->shouldReceive('cannot')->andReturnTrue();
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn($user->getKey());
        $guard = Mockery::mock(StatefulGuard::class);
        $guard->shouldReceive('user')->andReturn($user);
        $guard->shouldReceive('check')->andReturn(true);
        Filament::shouldReceive('auth')->andReturn($guard);

        FilamentShield::shouldReceive('getPages')
            ->andReturn([
                ChannelApplication::class => [
                    'permissions' => ['page_channels_access' => true],
                ],
            ]);
        Gate::define('page_channels_access', fn() => false);

        self::assertFalse(ChannelApplication::canAccess());
    }

    public function testCanAccessReturnsTrueWhenUserLacksPermission(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('can')->andReturnTrue();
        $user->shouldReceive('cannot')->andReturnTrue();
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn($user->getKey());
        $guard = Mockery::mock(StatefulGuard::class);
        $guard->shouldReceive('user')->andReturn($user);
        $guard->shouldReceive('check')->andReturn(true);
        Filament::shouldReceive('auth')->andReturn($guard);

        FilamentShield::shouldReceive('getPages')
            ->andReturn([
                ChannelApplication::class => [
                    'permissions' => ['page_channels_access' => true],
                ],
            ]);
        Gate::define('page_channels_access', fn() => true);
        Gate::define('page.channels.access', fn() => false);

        self::assertTrue(ChannelApplication::canAccess());
    }

    public function testCanAccessReturnsFalseWhenUserHasPermission(): void
    {
        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('can')->andReturnTrue();
        $user->shouldReceive('cannot')->andReturnFalse();
        Auth::shouldReceive('user')->andReturn($user);
        Auth::shouldReceive('id')->andReturn($user->getKey());
        $guard = Mockery::mock(StatefulGuard::class);
        $guard->shouldReceive('user')->andReturn($user);
        $guard->shouldReceive('check')->andReturn(true);
        Filament::shouldReceive('auth')->andReturn($guard);

        FilamentShield::shouldReceive('getPages')
            ->andReturn([
                ChannelApplication::class => [
                    'permissions' => ['page_channels_access' => true],
                ],
            ]);
        Gate::define('page_channels_access', fn() => true);
        Gate::define('page.channels.access', fn() => true);

        self::assertFalse(ChannelApplication::canAccess());
    }

    public function testNavigationTextsUseTranslations(): void
    {
        self::assertSame(
            __('filament.channel_application.title'),
            (new ChannelApplication())->getTitle()
        );
        self::assertSame(
            __('filament.channel_application.navigation_label'),
            ChannelApplication::getNavigationLabel()
        );
        self::assertSame(
            __('filament.channel_application.navigation_group'),
            ChannelApplication::getNavigationGroup()
        );
    }
}
