<?php

namespace App\Providers\Filament;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Standard\Pages\Auth\EditTenantProfile;
use App\Filament\Standard\Pages\Auth\Register;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\SetGuestLocale;
use App\Filament\Standard\Pages\ChannelApplication;
use App\Filament\Standard\Pages\Dashboard;
use App\Filament\Standard\Pages\MyOffers;
use App\Filament\Standard\Resources\VideoResource;
use App\Filament\Standard\Widgets\OnboardingWizard;
use App\Models\Team;
use App\Providers\Filament\Traits\PanelMiddlewareTrait;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use BezhanSalleh\FilamentShield\Middleware\SyncShieldTenant;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Foundation\Vite;
use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;

class PanelUserPanelProvider extends PanelProvider
{
    use PanelMiddlewareTrait;

    public function panel(Panel $panel): Panel
    {
        $this->addDefaults($panel);
        $this->addMiddlewares($panel);
        $panel->middleware([SetGuestLocale::class], isPersistent: true);
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
            ->brandLogo(fn () => view('filament.components.brand'))
            ->brandLogoHeight('48px')
            ->sidebarWidth('15rem')
            ->maxContentWidth(Width::Full)
            ->homeUrl(fn (): string => Filament::getTenant() ? Dashboard::getUrl() : route('home'))
            ->authGuard(GuardEnum::STANDARD->value)
            ->tenant(
                model: Team::class,
                slugAttribute: 'slug',
                ownershipRelationship: 'teams',
            )
            ->tenantMenu(false)
            ->tenantProfile(EditTenantProfile::class)
            ->profile(EditProfile::class)
            ->login(Login::class)
            ->registration(Register::class)
            ->emailVerification()
            ->emailChangeVerification()
            ->colors([
                'primary' => Color::Orange,
            ])
            ->favicon(asset('images/icons/favicon.ico'))
            ->viteTheme('resources/css/filament/standard/theme.css')
            ->assets([
                Js::make('app', app(Vite::class)->asset('resources/js/app.js'))->module(),
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
        return $panel->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('filament.components.support'))
            ->renderHook(PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, fn () => view('filament.components.login-register'), scopes: [Login::class])
            ->renderHook(
                PanelsRenderHook::CONTENT_END,
                function (): ?string {
                    return view('filament.components.footer')->render();
                }
            );
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
            FilamentPassportUiPlugin::make()->selfService(),
            LaravelWebdavServerFilamentPlugin::make()
                ->withoutAdminAccountResource()
                ->withUserAccountResource(),
        ]);
    }
}
