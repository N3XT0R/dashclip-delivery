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
        $this->addDefaults($panel);
        $this->addMiddlewares($panel);
        $this->addPlugins($panel);
        $this->addRenderHooks($panel);
        $this->addMFA($panel);
        $this->addWidgets($panel);

        return $panel;
    }

    protected function addDefaults(Panel $panel): Panel
    {
        return $panel->default()
            ->favicon(asset('images/icons/favicon.ico'))
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
                'primary' => Color::Red,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->pages([
                Dashboard::class,
            ])
            ->passwordReset()
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    protected function addRenderHooks(Panel $panel): Panel
    {
        return $panel->renderHook(
            PanelsRenderHook::FOOTER,
            function (): ?string {
                if (request()->routeIs('filament.admin.pages.video-upload')) {
                    return null;
                }

                return view('partials.footer')->render();
            }
        );
    }

    protected function addMiddlewares(Panel $panel): Panel
    {
        return $panel->middleware([
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

    protected function addPlugins(Panel $panel): Panel
    {
        return $panel->plugins([
            FilamentShieldPlugin::make(),
            FilamentLogViewerPlugin::make()
                ->navigationGroup('System')
                ->navigationLabel('Log Viewer'),
        ]);
    }

    protected function addMFA(Panel $panel): Panel
    {
        return $panel->multiFactorAuthentication([
            AppAuthentication::make()
                ->recoverable(),
            EmailAuthentication::make(),
        ]);
    }

    protected function addWidgets(Panel $panel): Panel
    {
        return $panel->widgets([
            AccountWidget::class,
            FilamentInfoWidget::class,
        ]);
    }
}
