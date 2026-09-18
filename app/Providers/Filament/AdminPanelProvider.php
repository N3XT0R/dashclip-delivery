<?php

namespace App\Providers\Filament;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Auth\PasskeyProvider;
use App\Filament\Admin\Pages\Auth\EditProfile;
use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\SetGuestLocale;
use App\Providers\Filament\Traits\PanelMiddlewareTrait;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Boquizo\FilamentLogViewer\FilamentLogViewerPlugin;
use Filament\Auth\MultiFactor\App\AppAuthentication;
use Filament\Auth\MultiFactor\Email\EmailAuthentication;
use Filament\Enums\ThemeMode;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Assets\Js;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Foundation\Vite;
use N3XT0R\FilamentPassportUi\FilamentPassportUiPlugin;
use N3XT0R\LaravelWebdavServerFilament\LaravelWebdavServerFilamentPlugin;

class AdminPanelProvider extends PanelProvider
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
        $this->addNotifications($panel);

        return $panel;
    }

    protected function addNotifications(Panel $panel): Panel
    {
        $panel->databaseNotifications();
        return $panel;
    }

    protected function addDefaults(Panel $panel): Panel
    {
        return $panel->default()
            ->favicon(asset('images/icons/favicon.ico'))
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->assets([
                Js::make('app', app(Vite::class)->asset('resources/js/app.js'))->module(),
            ])
            ->brandLogo(fn () => view('filament.components.brand'))
            ->brandLogoHeight('48px')
            ->sidebarWidth('15rem')
            ->maxContentWidth(Width::Full)
            ->defaultThemeMode(ThemeMode::Light)
            ->homeUrl(fn (): string => Filament::auth()->check() ? Dashboard::getUrl(panel: PanelEnum::ADMIN->value) : route('home'))
            ->id(PanelEnum::ADMIN->value)
            ->path(PanelEnum::ADMIN->value)
            ->authGuard(GuardEnum::DEFAULT->value)
            ->tenant(null)
            ->login(Login::class)
            ->emailVerification()
            ->emailChangeVerification()
            ->profile(EditProfile::class, false)
            ->colors([
                'primary' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\\Filament\\Admin\\Pages')
            ->discoverWidgets(in: app_path('Filament/Admin/Widgets'), for: 'App\\Filament\\Admin\\Widgets')
            ->discoverClusters(in: app_path('Filament/Admin/Clusters'), for: 'App\\Filament\\Admin\\Clusters')
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
        return $panel->renderHook(PanelsRenderHook::SIDEBAR_FOOTER, fn () => view('filament.components.support'))
            ->renderHook(
                PanelsRenderHook::CONTENT_END,
                function (): ?string {
                    return view('filament.components.footer')->render();
                }
            );
    }

    protected function addPlugins(Panel $panel): Panel
    {
        return $panel->plugins([
            FilamentShieldPlugin::make()
                ->centralApp()
                ->scopeToTenant(false)
                ->tenantRelationshipName('teams')
                ->tenantOwnershipRelationshipName('owner'),
            FilamentLogViewerPlugin::make()
                ->navigationGroup('System')
                ->navigationLabel('Log Viewer'),
            FilamentPassportUiPlugin::make(),
            LaravelWebdavServerFilamentPlugin::make(),
        ]);
    }

    protected function addMFA(Panel $panel): Panel
    {
        return $panel->multiFactorAuthentication([
            AppAuthentication::make()
                ->recoverable(),
            EmailAuthentication::make(),
            PasskeyProvider::make(),
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
