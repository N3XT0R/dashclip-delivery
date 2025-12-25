<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Observers\ChannelObserver;
use App\Observers\TeamObserver;
use App\Observers\UserObserver;
use App\Observers\VideoObserver;
use Illuminate\Support\ServiceProvider;

class ObserverServiceProvider extends ServiceProvider
{

    public function boot(): void
    {
        $this->bootObserver();
    }

    protected function bootObserver(): void
    {
        User::observe(UserObserver::class);
        Team::observe(TeamObserver::class);
        Video::observe(VideoObserver::class);
        Channel::observe(ChannelObserver::class);
    }
}
