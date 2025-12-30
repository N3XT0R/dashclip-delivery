<?php

namespace App\Providers;

use App\Facades\Cfg;
use App\Models\Permission;
use App\Models\Role;
use App\Repository\Contracts\ConfigRepositoryInterface;
use App\Repository\EloquentConfigRepository;
use App\Services\ConfigService;
use App\Services\Contracts\ConfigServiceInterface;
use App\Services\Contracts\UnzipServiceInterface;
use App\Services\Dropbox\AutoRefreshTokenProvider;
use App\Services\Mail\Scanner\Handlers\BounceHandler;
use App\Services\Mail\Scanner\Handlers\InboundHandler;
use App\Services\Mail\Scanner\Handlers\ReplyHandler;
use App\Services\Mail\Scanner\MailReplyScanner;
use App\Services\Zip\UnzipService;
use Carbon\CarbonInterval;
use Filament\Resources\Resource;
use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Passport;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;
use Spatie\Permission\PermissionRegistrar;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->registerConfig();
        $this->registerRefreshTokenProvider();
        $this->registerZip();
        $this->registerMail();
    }

    protected function registerConfig(): void
    {
        $this->app->bind(ConfigRepositoryInterface::class, EloquentConfigRepository::class);
        $this->app->bind(ConfigServiceInterface::class, ConfigService::class);
    }

    protected function registerZip(): void
    {
        $this->app->bind(UnzipServiceInterface::class, UnzipService::class);
    }

    protected function registerMail(): void
    {
        $this->app->singleton(MailReplyScanner::class, function () {
            $handlers = [
                $this->app->get(BounceHandler::class),
                $this->app->get(InboundHandler::class),
            ];

            if (Cfg::get('faq_email', 'email', true)) {
                $handlers[] = $this->app->get(ReplyHandler::class);
            }

            return new MailReplyScanner($handlers);
        });
    }

    protected function registerRefreshTokenProvider(): void
    {
        $this->app->singleton(AutoRefreshTokenProvider::class, function (Application $app) {
            $cfg = config('filesystems.disks.dropbox');
            /**
             * @var ConfigServiceInterface $configService
             */
            $configService = $app->make(ConfigServiceInterface::class);

            return new AutoRefreshTokenProvider(
                (string)($cfg['client_id'] ?: ''),
                (string)($cfg['client_secret'] ?: ''),
                $configService->get(key: 'dropbox_refresh_token', category: 'oauth', withoutCache: true),
                Cache::store()
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Resource::scopeToTenant(false);
        app(PermissionRegistrar::class)
            ->setPermissionClass(Permission::class)
            ->setRoleClass(Role::class);

        Storage::extend('dropbox', static function ($app, $config) {
            $client = new DropboxClient(app(AutoRefreshTokenProvider::class));
            $root = trim((string)($config['root'] ?? ''), '/');
            $adapter = new DropboxAdapter($client, $root);

            $filesystem = new Filesystem($adapter);
            return new FilesystemAdapter($filesystem, $adapter, $config);
        });
        $this->bootPassport();
    }

    protected function bootPassport(): void
    {
        Passport::tokensExpireIn(CarbonInterval::days(1));
        Passport::refreshTokensExpireIn(CarbonInterval::days(30));
        Passport::personalAccessTokensExpireIn(CarbonInterval::month());
    }
}
