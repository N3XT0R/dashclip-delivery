<?php

declare(strict_types=1);

use App\Enum\PanelEnum;
use App\Http\Middleware\Api\EnsureStandardPermission;
use Filament\Facades\Filament;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Laravel\Passport\Http\Middleware\CheckToken;
use Laravel\Passport\Http\Middleware\CheckTokenForAnyScope;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['cookie_consent']);
        $middleware->alias([
            'scope' => CheckTokenForAnyScope::class,
            'scopes' => CheckToken::class,
            'standard.permission' => EnsureStandardPermission::class,
        ]);
        // Pages outside the panels that need a signed-in user (e.g. the OAuth consent) use the
        // sign-in of the user area; the panels redirect to their own sign-in themselves.
        $middleware->redirectGuestsTo(
            static fn (): string => Filament::getPanel(PanelEnum::STANDARD->value)->getLoginUrl()
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            static fn ($request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
