<?php

declare(strict_types=1);

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
use App\Events\ActionToken\ActionTokenConsumed;
use App\Events\Channel\ChannelVideoReceptionPaused;
use App\Listeners\Channel\HandleChannelReceptionReactivation;
use App\Listeners\SendChannelVideoReceptionPausedMail;
use App\Listeners\WebDav\ZipUploadedListener;
use Filament\Resources\Resource;
use GrahamCampbell\GuzzleFactory\GuzzleFactory;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileCreatedEvent;
use N3XT0R\LaravelWebdavServer\Events\WebDav\FileUpdatedEvent;
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
        Event::listen(
            [FileCreatedEvent::class, FileUpdatedEvent::class],
            ZipUploadedListener::class
        );

        Event::listen(ActionTokenConsumed::class, HandleChannelReceptionReactivation::class);
        Event::listen(ChannelVideoReceptionPaused::class, SendChannelVideoReceptionPausedMail::class);

        Resource::scopeToTenant(false);
        app(PermissionRegistrar::class)
            ->setPermissionClass(Permission::class)
            ->setRoleClass(Role::class);

        Storage::extend('dropbox', static function ($app, $config) {
            $client = new DropboxClient(
                app(AutoRefreshTokenProvider::class),
                // stream:true prevents Guzzle from buffering the response body via
                // php://temp into /tmp; the file data streams directly from the socket.
                // GuzzleFactory::handler() preserves Spatie's built-in retry logic.
                new GuzzleClient(['handler' => GuzzleFactory::handler(), 'stream' => true]),
            );
            $root = trim((string)($config['root'] ?? ''), '/');
            $adapter = new DropboxAdapter($client, $root);

            return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
        });
    }
}
