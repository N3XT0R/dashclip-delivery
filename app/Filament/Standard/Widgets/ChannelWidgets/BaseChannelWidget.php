<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets\ChannelWidgets;

use App\Models\Channel;
use App\Repository\UserRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class BaseChannelWidget extends BaseWidget
{
    protected Channel|null $channel = null;

    protected static bool $isLazy = false;

    public function mount(?Channel $channel = null): void
    {
        if ($channel) {
            $this->setChannel($channel);
        }
    }

    public function getChannel(): ?Channel
    {
        return $this->channel;
    }

    public function setChannel(?Channel $channel): void
    {
        $this->channel = $channel;
    }

    protected function getCurrentChannel(): ?Channel
    {
        $user = app(UserRepository::class)->getCurrentUser();
        if (!$user) {
            return null;
        }


        $currentChannel = $this->getChannel();
        if ($currentChannel) {
            return $currentChannel;
        }


        return $user->channels()
            ->latest()
            ->first();
    }
}