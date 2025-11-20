<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class StandardPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $this->addDefaults($panel);
        return $panel;
    }

    protected function addDefaults(Panel $panel): Panel
    {
        $panel
            ->id('standard')
            ->path('standard')
            ->login()
            ->emailVerification()
            ->emailChangeVerification()
            ->profile(EditProfile::class, false)
            ->colors([
                'primary' => Color::Sky,
            ])
            ->favicon(asset('images/icons/favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->assets([
                Js::make('app', app(Vite::class)->asset('resources/js/app.js')),
                Css::make('app', app(Vite::class)->asset('resources/css/app.css')),
            ])
            ->pages([
                Dashboard::class,
            ])
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Standard/Widgets'), for: 'App\Filament\Standard\Widgets')
            ->discoverResources(in: app_path('Filament/Standard/Resources'), for: 'App\Filament\Standard\Resources')
            ->discoverPages(in: app_path('Filament/Standard/Pages'), for: 'App\Filament\Standard\Pages')
            ->passwordReset()
            ->authMiddleware([
                Authenticate::class,
            ]);
        return $panel;
    }
}
