<?php

declare(strict_types=1);

namespace App\Repository;

use App\Enum\Channel\ApplicationEnum;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

class ChannelRepository
{
    /**
     * Get all active channels where video reception is not paused.
     * @return Collection<Channel>
     */
    public function getActiveChannels(): Collection
    {
        return Channel::query()
            ->where('channels.is_video_reception_paused', false)
            ->orderBy('id')->get();
    }

    /**
     * Approve a channel by updating its status.
     * @param Channel $channel
     * @return bool
     */
    public function approve(Channel $channel): bool
    {
        return $channel->update([
            'is_video_reception_paused' => false,
            'approved_at' => now(),
        ]);
    }

    /**
     * Find channels based on an optional argument and a force flag.
     *
     * This method is a direct data-access counterpart of the logic used
     * in the SendChannelWelcomeMailCommand — but stripped of any business
     * or presentation logic. It simply builds and executes the query.
     *
     * @param string|null $arg Channel ID or e-mail address (optional)
     * @param bool $force If true, includes already approved channels
     *
     * @return Collection<Channel>
     */
    /**
     * Return all channels that are pending approval.
     *
     * These are channels where 'approved_at' is null.
     *
     * @return Collection<Channel>
     */
    public function getPendingApproval(): Collection
    {
        return Channel::query()
            ->whereNull('approved_at')
            ->get();
    }

    /**
     * Find a channel by its numeric ID.
     *
     * @param int $id
     * @return Channel|null
     */
    public function findById(int $id): ?Channel
    {
        return Channel::query()->find($id);
    }

    /**
     * Find a channel by its email address.
     *
     * @param string $email
     * @return Channel|null
     */
    public function findByEmail(string $email): ?Channel
    {
        return Channel::query()
            ->where('email', $email)
            ->first();
    }

    /**
     * Get channels assigned to a specific team.
     * @param Team $team
     * @return SupportCollection<Channel>
     */
    public function getTeamAssignedChannels(Team $team): SupportCollection
    {
        return $team->assignedChannels()->get();
    }

    /**
     * Create a new channel application with the given attributes.
     * @param array $attributes
     * @return ChannelApplication
     */
    public function createApplication(array $attributes): ChannelApplication
    {
        return ChannelApplication::create($attributes);
    }

    /**
     * Get channel applications for a specific user, optionally filtered by status.
     * @param User $user
     * @param ApplicationEnum|null ...$byStatus
     * @return Collection<ChannelApplication>
     */
    public function getChannelApplicationsByUser(User $user, ?ApplicationEnum ...$byStatus): Collection
    {
        return ChannelApplication::query()
            ->where('user_id', $user->getKey())
            ->when(
                filled($byStatus),
                fn($query) => $query->whereIn('status', array_column($byStatus, 'value'))
            )->get();
    }

    /**
     * Assign a user to a channel.
     * @param User $user
     * @param Channel $channel
     * @return bool
     */
    public function assignUserToChannel(User $user, Channel $channel): bool
    {
        $channel->channelUsers()->attach([$user->getKey()]);

        return $channel->channelUsers()
            ->where('user_id', $user->getKey())
            ->exists();
    }

    /**
     * Unassign a user from a channel.
     * @param User $user
     * @param Channel $channel
     * @return bool
     */
    public function unassignUserFromChannel(User $user, Channel $channel): bool
    {
        $channel->channelUsers()->detach([$user->getKey()]);

        return !$channel->channelUsers()
            ->where('user_id', $user->getKey())
            ->exists();
    }

    /**
     * Check if a user has access to a specific channel.
     * @param User $user
     * @param Channel $channel
     * @return bool
     */
    public function hasUserAccessToChannel(User $user, Channel $channel): bool
    {
        return $channel->channelUsers()
            ->where('user_id', $user->getKey())
            ->exists();
    }

    /**
     * Check if a user is verified for a specific channel.
     * @param User $user
     * @param Channel $channel
     * @return bool
     */
    public function isUserVerifiedForChannel(User $user, Channel $channel): bool
    {
        return $channel->channelUsers()
            ->where('user_id', $user->getKey())
            ->wherePivot('is_user_verified', true)
            ->exists();
    }

    /**
     * Check if a user has access to any channel.
     * @param User $user
     * @return bool
     */
    public function hasUserAccessToAnyChannel(User $user): bool
    {
        return $user->channels()
            ->where('is_user_verified', true)
            ->exists();
    }

    /**
     * Find a channel by its name.
     * @param string $name
     * @return Channel|null
     */
    public function findByName(string $name): ?Channel
    {
        return Channel::query()
            ->where('name', trim($name))
            ->first();
    }

    public function createChannel(array $attributes): Channel
    {
        return Channel::create($attributes);
    }
}
