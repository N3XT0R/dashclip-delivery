<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Boquizo\FilamentLogViewer\FilamentLogViewerPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
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
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panelObj = $panel
            ->default()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->assets([
                Js::make('app', app(Vite::class)->asset('resources/js/app.js')),
                Css::make('app', app(Vite::class)->asset('resources/css/app.css')),
            ])
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('75px')
            ->id('admin')
            ->path('admin')
            ->login()
            ->emailVerification()
            ->emailChangeVerification()
            ->profile(EditProfile::class, false)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->passwordReset()
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->multiFactorAuthentication([
                AppAuthentication::make()
                    ->recoverable(),
                EmailAuthentication::make(),
            ]);

        $this->addMiddlewares($panelObj);
        $this->addPlugins($panelObj);
        $this->addRenderHooks($panelObj);

        return $panelObj;
    }

    protected function addRenderHooks(Panel $panel): void
    {
        $panel->renderHook(
            PanelsRenderHook::FOOTER,
            fn(): string => view('partials.footer')->render()
        );
    }

    protected function addMiddlewares(Panel $panel): void
    {
        $panel->middleware([
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            AuthenticateSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
            DisableBladeIconComponents::class,
            DispatchServingFilamentEvent::class,
        ]);
    }

    protected function addPlugins(Panel $panel): void
    {
        $panel->plugins([
            FilamentShieldPlugin::make(),
            FilamentLogViewerPlugin::make()
                ->navigationGroup('System')
                ->navigationLabel('Log Viewer'),
        ]);
    }
}
