<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Traits;

use App\Application\Channel\GetCurrentChannel;
use App\Models\Channel;

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
}
