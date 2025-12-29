<?php

declare(strict_types=1);

namespace App\Filament\Traits;

use App\Application\Channel\GetCurrentChannel;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait to provide channel owner context functionality.
 */
trait ChannelOwnerContextTrait
{
    /**
     * Get the current channel.
     * @return Channel|null
     * @throws \Exception
     */
    protected function getCurrentChannel(): ?Channel
    {
        return app(GetCurrentChannel::class)->handle();
    }

    /**
     * Get the current channel only if the user has access to it.
     * @return Channel|null
     * @throws \Exception
     */
    protected function getCurrentChannelOnlyIfHaveAccess(): ?Channel
    {
        $channel = $this->getCurrentChannel();
        if (null === $channel) {
            return null;
        }
        $user = auth()->user();
        if (static::userHasAccessToChannel($user) === false) {
            return null;
        }
        return $channel;
    }

    /**
     * Check if the user has access to the given channel.
     * @param Channel|Model|null $channel
     * @param User|null $user
     * @return bool
     */
    public static function userHasAccessToChannel(Channel|Model|null $channel, ?User $user = null): bool
    {
        if ($channel instanceof Channel == false) {
            return false;
        }

        $user ??= auth()->user();
        return !(null === $user || !$user->can('page.channels.access_for_channel', $channel));
    }
}
