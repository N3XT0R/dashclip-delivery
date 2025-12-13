<?php

declare(strict_types=1);

namespace App\Filament\Standard\Pages\Widgets;

use App\Enum\StatusEnum;
use App\Models\Assignment;
use App\Models\Channel;
use Filament\Facades\Filament;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MyOffersStats extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $assignments = $this->availableAssignments()->get();

        $availableCount = $assignments->count();
        $downloadedFromAvailable = $this->countDownloaded($assignments);
        $averageValidity = $availableCount > 0
            ? $this->formatDays($assignments->avg(fn (Assignment $assignment) => now()->diffInDays($assignment->expires_at)))
            : $this->formatDays(0);

        return [
            Card::make(__('filament.my_offers.stats.available_total'), $availableCount)
                ->color('success')
                ->icon('heroicon-o-video-camera'),
            Card::make(__('filament.my_offers.stats.available_downloaded'), $downloadedFromAvailable)
                ->color('primary')
                ->icon('heroicon-o-arrow-down-tray'),
            Card::make(__('filament.my_offers.stats.average_validity'), $averageValidity)
                ->color('gray')
                ->icon('heroicon-o-clock'),
        ];
    }

    protected function availableAssignments(): Builder
    {
        return Assignment::query()
            ->where('channel_id', $this->getChannel()?->getKey())
            ->whereNotIn('status', [
                StatusEnum::PICKEDUP->value,
                StatusEnum::EXPIRED->value,
                StatusEnum::REJECTED->value,
            ])
            ->where('expires_at', '>', now())
            ->with(['downloads']);
    }

    protected function getChannel(): ?Channel
    {
        return Filament::auth()->user()?->channels()->first();
    }

    protected function countDownloaded(Collection $assignments): int
    {
        return $assignments->filter(fn (Assignment $assignment) => $assignment->downloads->isNotEmpty())->count();
    }

    protected function formatDays(float|int $days): string
    {
        return number_format((float) $days, 1, ',', '.') . ' ' . __('filament.my_offers.stats.days');
    }
}
