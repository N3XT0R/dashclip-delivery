<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets\ChannelWidgets;

use App\Models\Channel;
use App\Repository\ChannelRepository;
use App\Repository\UserRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

abstract class BaseChannelWidget extends BaseWidget
{
    public ?int $channelId = null;

    protected static bool $isLazy = false;


    public function mount(?int $channelId = null): void
    {
        if ($channelId) {
            $this->channelId = $channelId;
        }
    }

    protected function getChannel(): ?Channel
    {
        if ($this->channelId) {
            return app(ChannelRepository::class)->findById($this->channelId);
        }

        return $this->resolveFallbackChannel();
    }

    protected function resolveFallbackChannel(): ?Channel
    {
        $user = app(UserRepository::class)->getCurrentUser();

        return $user?->channels()->latest()->first();
    }
}
