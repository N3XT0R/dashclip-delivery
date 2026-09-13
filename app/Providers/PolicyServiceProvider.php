<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\Abilities\AccessChannelPageAbility;
use App\Models\Channel;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\PostTag;
use App\Policies\PostPolicy;
use App\Policies\PostCategoryPolicy;
use App\Policies\PostTagPolicy;
use App\Models\Team;
use App\Models\User;
use App\Policies\TeamPolicy;
use App\Policies\WebDavPathPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use N3XT0R\LaravelWebdavServer\DTO\Auth\PathResourceDto;

class PolicyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Gate::policy(Post::class, PostPolicy::class);
        Gate::policy(PostCategory::class, PostCategoryPolicy::class);
        Gate::policy(PostTag::class, PostTagPolicy::class);
        Gate::policy(Team::class, TeamPolicy::class);
        Gate::policy(PathResourceDto::class, WebDavPathPolicy::class);
        $this->bootAbilities();
    }

    protected function bootAbilities(): void
    {
        Gate::define(
            'page.channels.access',
            static fn (User $user) => app(AccessChannelPageAbility::class)->check($user)
        );
        Gate::define(
            'page.channels.access_for_channel',
            static fn (User $user, Channel $channel) => app(AccessChannelPageAbility::class)->checkForChannel(
                $user,
                $channel
            )
        );
    }
}
