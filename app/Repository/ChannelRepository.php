<?php

declare(strict_types=1);

namespace App\Repository;

use App\Models\Channel;
use Illuminate\Database\Eloquent\Collection;

class ChannelRepository
{
    public function getActiveChannels(): Collection
    {
        return Channel::query()
            ->where('channels.is_video_reception_paused', false)
            ->orderBy('id')->get();
    }

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
     * @param  string|null  $arg  Channel ID or e-mail address (optional)
     * @param  bool  $force  If true, includes already approved channels
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
     * @param  int  $id
     * @return Channel|null
     */
    public function findById(int $id): ?Channel
    {
        return Channel::query()->find($id);
    }

    /**
     * Find a channel by its email address.
     *
     * @param  string  $email
     * @return Channel|null
     */
    public function findByEmail(string $email): ?Channel
    {
        return Channel::query()
            ->where('email', $email)
            ->first();
    }
}