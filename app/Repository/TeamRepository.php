<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TeamRepository
{
    /**
     * Create a personal team for the given user.
     * @param  User  $user
     * @return Team
     */
    public function createOwnTeamForUser(User $user): Team
    {
        $team = Team::query()->create([
            'name' => $user->name."'s Team",
            'owner_id' => $user->getKey(),
        ]);

        $user->teams()->attach($team);

        return $team;
    }

    /**
     * Query the teams visible to the given user: teams they own or teams
     * they are a member of.
     * @param User $user
     * @return Builder
     */
    public function visibleForUser(User $user): Builder
    {
        return Team::query()->where(function (Builder $query) use ($user): void {
            $query->where('owner_id', $user->getKey())
                ->orWhereHas('users', static function (Builder $member) use ($user): void {
                    $member->whereKey($user->getKey());
                });
        });
    }

    /**
     * Create a team owned by the given user, generating a unique slug from
     * the team name, and attach the owner as a member.
     * @param User $owner
     * @param string $name
     * @return Team
     */
    public function createTeamForOwner(User $owner, string $name): Team
    {
        return DB::transaction(function () use ($owner, $name): Team {
            $team = Team::query()->create([
                'name' => $name,
                'slug' => Str::slug($name) . '-' . Str::lower(Str::random(6)),
                'owner_id' => $owner->getKey(),
            ]);

            $team->users()->attach($owner->getKey());

            return $team;
        });
    }

    /**
     * Get the default personal team for the given user.
     * @param  User  $user
     * @return Team|null
     */
    public function getDefaultTeamForUser(User $user): ?Team
    {
        return $user->teams()->isOwnTeam($user)->first();
    }

    /**
     * Check if the user can access the given team.
     * @param  User  $user
     * @param  Team  $team
     * @return bool
     */
    public function canAccessTeam(User $user, Team $team): bool
    {
        return $user->teams()->where('teams.id', $team->getKey())->exists();
    }

    /**
     * Check if the user is the owner of the given team.
     * @param  User  $user
     * @param  Team  $team
     * @return bool
     */
    public function isUserOwnerOfTeam(User $user, Team $team): bool
    {
        return $team->owner_id === $user->getKey();
    }

    /**
     * Check if the user is a member of the given team.
     * @param  User  $user
     * @param  Team  $team
     * @return bool
     */
    public function isMemberOfTeam(User $user, Team $team): bool
    {
        return $team->users()->wherePivot('user_id', $user->getKey())->exists();
    }

    /**
     * Find a team by its unique slug.
     * @param  string  $slug
     * @return Team|null
     */
    public function getTeamByUniqueSlug(string $slug): ?Team
    {
        return Team::query()
            ->where('slug', $slug)
            ->first();
    }
}
