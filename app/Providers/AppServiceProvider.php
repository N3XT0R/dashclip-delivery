<?php

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Repository\Contracts\ConfigRepositoryInterface;
use App\Repository\EloquentConfigRepository;
use App\Services\ConfigService;
use App\Services\Contracts\ConfigServiceInterface;
use App\Services\Contracts\UnzipServiceInterface;
use App\Services\Dropbox\AutoRefreshTokenProvider;
use App\Services\Ingest\Contracts\IngestPipelineInterface;
use App\Services\Ingest\Contracts\IngestStepInterface;
use App\Services\Ingest\IngestPipeline;
use App\Services\Mail\Scanner\Detectors\BounceDetector;
use App\Services\Mail\Scanner\Detectors\ReplyDetector;
use App\Services\Mail\Scanner\Handlers\BounceHandler;
use App\Services\Mail\Scanner\Handlers\InboundHandler;
use App\Services\Mail\Scanner\Handlers\ReplyHandler;
use App\Services\Mail\Scanner\MailReplyScanner;
use App\Services\Zip\UnzipService;
use Illuminate\Contracts\Container\Container as Application;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Spatie\Dropbox\Client as DropboxClient;
use Spatie\FlysystemDropbox\DropboxAdapter;

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
        $this->registerIngest();
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
            return new MailReplyScanner([
                new BounceHandler(),
                $this->app->get(InboundHandler::class),
                $this->app->get(ReplyHandler::class),
            ]);
        });
    }

    protected function registerIngest(): void
    {
        $this->app->bind(IngestPipelineInterface::class, function (Application $app) {
            $instances = [];
            $steps = $app['config']->get('ingest.steps', []);
            if (!empty($steps)) {
                foreach ($steps as $stepClass) {
                    $step = $app->make($stepClass);
                    if ($step instanceof IngestStepInterface) {
                        $instances[] = $app->make($stepClass);
                    }
                }
            }
            return new IngestPipeline($instances);
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
        Storage::extend('dropbox', function ($app, $config) {
            $client = new DropboxClient(app(AutoRefreshTokenProvider::class));
            $root = trim((string)($config['root'] ?? ''), '/');
            $adapter = new DropboxAdapter($client, $root);

            $filesystem = new Filesystem($adapter);
            return new FilesystemAdapter($filesystem, $adapter, $config);
        });

        $this->bootObserver();
    }

    protected function bootObserver(): void
    {
        User::observe(UserObserver::class);
    }
}
