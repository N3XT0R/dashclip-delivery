<?php

namespace App\Providers\Filament;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Pages\Auth\EditProfile;
use App\Filament\Standard\Pages\Auth\EditTenantProfile;
use App\Filament\Standard\Pages\Auth\Register;
use App\Filament\Standard\Pages\ChannelApplication;
use App\Filament\Standard\Pages\Dashboard;
use App\Filament\Standard\Pages\MyOffers;
use App\Filament\Standard\Resources\VideoResource;
use App\Filament\Standard\Widgets\OnboardingWizard;
use App\Models\Team;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
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
        $this->addNotifications($panel);
        $this->customizeNavigation($panel);

        return $panel;
    }


    protected function customizeNavigation(Panel $panel): Panel
    {
        $panel->navigationGroups([
            __('nav.media'),
            __('nav.settings'),
            NavigationGroup::make('channel_owner')
                ->label(__('nav.channel_owner'))
                ->items([
                    ChannelApplication::class,
                    MyOffers::class,
                ]),
        ]);
        return $panel;
    }

    protected function addNotifications(Panel $panel): Panel
    {
        $panel->databaseNotifications();
        return $panel;
    }

    protected function addDefaults(Panel $panel): Panel
    {
        $panel
            ->id(PanelEnum::STANDARD->value)
            ->path(PanelEnum::STANDARD->value)
            ->defaultThemeMode(ThemeMode::Light)
            ->favicon(asset('images/icons/favicon.ico'))
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('75px')
            ->homeUrl('dashboard')
            ->authGuard(GuardEnum::STANDARD->value)
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
                Dashboard::class,
            ])
            ->resources([
                VideoResource::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Standard/Widgets'), for: 'App\Filament\Standard\Widgets')
            ->discoverResources(in: app_path('Filament/Standard/Resources'), for: 'App\Filament\Standard\Resources')
            ->discoverPages(in: app_path('Filament/Standard/Pages'), for: 'App\Filament\Standard\Pages')
            ->discoverClusters(in: app_path('Filament/Standard/Clusters'), for: 'App\\Filament\\Standard\\Clusters')
            ->passwordReset()
            ->authMiddleware([
                Authenticate::class,
            ]);
        return $panel;
    }

    protected function addRenderHooks(Panel $panel): Panel
    {
        return $panel->renderHook(
            PanelsRenderHook::CONTENT_END,
            function (): ?string {
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
            OnboardingWizard::class,
            AccountWidget::class,
        ]);
    }

    protected function addPlugins(Panel $panel): Panel
    {
        return $panel->plugins([
            FilamentShieldPlugin::make()
                ->registerNavigation(false)
                ->centralApp(false)
                ->localizePermissionLabels()
                ->scopeToTenant(false),
        ]);
    }
}
