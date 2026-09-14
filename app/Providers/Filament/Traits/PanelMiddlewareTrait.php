<?php

declare(strict_types=1);

namespace App\Providers\Filament\Traits;

use App\Http\Middleware\RecordUserActivity;
use App\Http\Middleware\SetUserLocale;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

trait PanelMiddlewareTrait
{
    /**
     * Register the shared panel middleware stack, including the persistent middleware that also
     * runs on Livewire requests.
     */
    protected function addMiddlewares(Panel $panel): Panel
    {
        $panel->middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            PreventRequestForgery::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ]);

        return $panel->middleware([
            SetUserLocale::class,
            RecordUserActivity::class,
        ], isPersistent: true);
    }
}
