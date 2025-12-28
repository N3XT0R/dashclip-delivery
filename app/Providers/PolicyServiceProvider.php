<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\Abilities\AccessChannelPageAbility;
use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class PolicyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Team::class, TeamPolicy::class);
        $this->bootAbilities();
    }

    protected function bootAbilities(): void
    {
        Gate::define(
            'page.channels.access',
            static fn(User $user) => app(AccessChannelPageAbility::class)->check($user)
        );
        Gate::define(
            'page.channels.access_for_channel',
            static fn(User $user, Channel $channel) => app(AccessChannelPageAbility::class)->checkForChannel(
                $user,
                $channel
            )
        );
    }
}
