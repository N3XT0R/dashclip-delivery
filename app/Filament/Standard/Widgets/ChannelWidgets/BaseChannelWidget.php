<?php

declare(strict_types=1);

namespace App\Filament\Standard\Widgets\ChannelWidgets;

use App\Models\Channel;
use App\Repository\UserRepository;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;

class BaseChannelWidget extends BaseWidget
{
    protected Channel|null $channel = null;

    public function mount(?Channel $channel = null): void
    {
        $this->setChannel($channel);
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
        return $user->channels()->firstOrFail();
    }
}