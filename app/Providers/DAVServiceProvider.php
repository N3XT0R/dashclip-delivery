<?php

declare(strict_types=1);

namespace App\Providers;

use Generator;
use Illuminate\Support\ServiceProvider;
use LaravelSabre\Http\Auth\AuthBackend;
use LaravelSabre\LaravelSabre;
use Sabre\CardDAV\Plugin as CardDAVPlugin;
use Sabre\DAV\Auth\Plugin as AuthPlugin;

class DAVServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        LaravelSabre::plugins(function () {
            return $this->plugins();
        });
    }

    /**
     * List of Sabre plugins.
     */
    private function plugins(): Generator
    {
        // Authentication backend
        $authBackend = new AuthBackend();
        yield new AuthPlugin($authBackend);

        // CardDAV plugin
        yield new CardDAVPlugin();
    }
}
