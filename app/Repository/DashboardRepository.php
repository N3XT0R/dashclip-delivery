<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use N3XT0R\LaravelWebdavServer\Models\WebDavAccountModel;

readonly class DashboardRepository
{
    public function __construct(private VideoRepository $videos, private DownloadRepository $downloads)
    {
    }

    /** @return Builder<Video> Videos owned by the user in the selected team. */
    public function videos(User $user, Team $team): Builder
    {
        return $this->videos->visibleForUser($user)->where('team_id', $team->getKey());
    }

    /** @return Collection<int, Video> The five newest videos, with preview metadata loaded. */
    public function recentVideos(User $user, Team $team): Collection
    {
        return $this->videos($user, $team)
            ->with(['clips' => fn (HasMany $query) => $query->where('user_id', $user->getKey())->orderBy('id')])
            ->latest()->orderByDesc('id')->limit(5)->get();
    }

    /** Count downloads of this user's videos in the selected team during the last 30 days. */
    public function recentDownloadCount(User $user, Team $team): int
    {
        return $this->downloads->forUser($user)
            ->whereHas('assignment.video', fn (Builder $query) => $query->where('team_id', $team->getKey()))
            ->where('downloaded_at', '>=', now()->subDays(30))->count();
    }

    public function activeChannelCount(Team $team): int
    {
        return $team->assignedChannels()->count();
    }

    public function hasWebDavAccount(User $user): bool
    {
        return WebDavAccountModel::query()->where('user_id', $user->getKey())->where('enabled', true)->exists();
    }
}
