<?php

namespace App\Providers\Filament;

use App\Enum\PanelEnum;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Pages\VideoUpload;
use App\Filament\Standard\Pages\Auth\EditTenantProfile;
use App\Filament\Standard\Pages\Auth\Register;
use App\Models\Team;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;
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
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Foundation\Vite;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class PanelUserPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $this->addDefaults($panel);
        $this->addMiddlewares($panel);
        $this->addPlugins($panel);
        $this->addRenderHooks($panel);
        $this->addMFA($panel);
        $this->addWidgets($panel);
        $this->addTenantMiddlewares($panel);
        return $panel;
    }

    protected function addDefaults(Panel $panel): Panel
    {
        $panel
            ->id(PanelEnum::STANDARD->value)
            ->path(PanelEnum::STANDARD->value)
            ->homeUrl('dashboard')
            ->authGuard(PanelEnum::STANDARD->value)
            ->tenant(
                model: Team::class,
                slugAttribute: 'slug',
                ownershipRelationship: 'teams',
            )
            ->tenantMenu(false)
            ->tenantProfile(EditTenantProfile::class)
            ->profile(EditProfile::class)
            ->login()
            ->registration(Register::class)
            ->emailVerification()
            ->emailChangeVerification()
            ->colors([
                'primary' => Color::Slate,
            ])
            ->favicon(asset('images/icons/favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->assets([
                Js::make('app', app(Vite::class)->asset('resources/js/app.js')),
                Css::make('app', app(Vite::class)->asset('resources/css/app.css')),
            ])
            ->pages([
                VideoUpload::class,
                Dashboard::class,
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

    protected function addRenderHooks(Panel $panel): Panel
    {
        return $panel->renderHook(
            PanelsRenderHook::FOOTER,
            function (): ?string {
                if (request()->routeIs('filament.standard.pages.video-upload')) {
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

    protected function addTenantMiddlewares(Panel $panel): Panel
    {
        return $panel->tenantMiddleware([
            SyncShieldTenant::class,
        ], isPersistent: true);
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
        ]);
    }

    protected function addPlugins(Panel $panel): Panel
    {
        return $panel->plugins([
            FilamentShieldPlugin::make()
                ->localizePermissionLabels()
                ->scopeToTenant(false),
        ]);
    }
}
