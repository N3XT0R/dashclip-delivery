<?php

declare(strict_types=1);

namespace Tests\Integration\Providers\Filament;

use App\Enum\PanelEnum;
use App\Http\Middleware\RecordUserActivity;
use App\Http\Middleware\SetUserLocale;
use Filament\Facades\Filament;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class PanelMiddlewareTest extends TestCase
{
    /**
     * @return array<string, array{0: PanelEnum}>
     */
    public static function panelProvider(): array
    {
        return [
            'admin' => [PanelEnum::ADMIN],
            'standard' => [PanelEnum::STANDARD],
        ];
    }

    #[DataProvider('panelProvider')]
    public function testPanelRegistersTheSharedMiddlewareStack(PanelEnum $panel): void
    {
        $middleware = Filament::getPanel($panel->value)->getMiddleware();

        foreach ([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ] as $expected) {
            self::assertContains($expected, $middleware);
        }
    }

    #[DataProvider('panelProvider')]
    public function testPanelRegistersLocaleAndActivityMiddlewareAsPersistent(PanelEnum $panel): void
    {
        $middleware = Filament::getPanel($panel->value)->getMiddleware();

        self::assertContains(SetUserLocale::class, $middleware);
        self::assertContains(RecordUserActivity::class, $middleware);
    }

    #[DataProvider('panelProvider')]
    public function testEveryRegisteredPanelMiddlewareIsResolvable(PanelEnum $panel): void
    {
        foreach (Filament::getPanel($panel->value)->getMiddleware() as $middleware) {
            $class = explode(':', $middleware)[0];

            if (!str_contains($class, '\\')) {
                continue;
            }

            self::assertTrue(class_exists($class), sprintf('Middleware %s does not exist.', $class));
        }
    }
}
