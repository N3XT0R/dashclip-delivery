<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Widgets\ChannelWidgets;

use App\Enum\StatusEnum;
use App\Filament\Standard\Widgets\ChannelWidgets\DownloadedOffersStatsWidget;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Download;
use Carbon\Carbon;
use Tests\DatabaseTestCase;

final class DownloadedOffersStatsWidgetTest extends DatabaseTestCase
{
    public function testReturnsZeroStatsWhenChannelUnavailable(): void
    {
        $widget = new DownloadedOffersStatsWidget();

        $stats = $this->callProtectedMethod($widget, 'getStats');

        $this->assertCount(3, $stats);
        $this->assertSame(0, $stats[0]->getValue());
        $this->assertSame(0, $stats[1]->getValue());
        $this->assertSame(0, $stats[2]->getValue());
    }

    public function testComputesDownloadStatistics(): void
    {
        $now = now();

        $channel = Channel::factory()->create();

        $assignmentToday = Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::PICKEDUP->value,
            'created_at' => $now,
        ]);

        $assignmentThisWeek = Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::PICKEDUP->value,
            'created_at' => $now->copy()->subDays(3),
        ]);

        $assignmentOld = Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::PICKEDUP->value,
            'created_at' => $now->copy()->subDays(6),
        ]);

        $recentDownload = $now;
        $yesterdayDownload = $now->copy()->subDay();
        $olderDownload = $now->copy()->subDays(5);

        Download::factory()->forAssignment($assignmentToday)->at($recentDownload)->create();
        Download::factory()->forAssignment($assignmentThisWeek)->at($yesterdayDownload)->create();
        Download::factory()->forAssignment($assignmentOld)->at($olderDownload)->create();

        $widget = new DownloadedOffersStatsWidget();
        $widget->channelId = $channel->getKey();

        $stats = $this->callProtectedMethod($widget, 'getStats');
        $chartData = $this->callProtectedMethod($widget, 'getDownloadedChartData', [$channel]);

        $expectedChart = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = $now->copy()->subDays($i)->toDateString();
            $expectedChart[] = collect([
                $recentDownload,
                $yesterdayDownload,
                $olderDownload,
            ])->filter(fn(Carbon $downloadDate) => $downloadDate->toDateString() === $date)->count();
        }

        $this->assertSame('3', $stats[0]->getValue());
        $weeklyCount = collect([$recentDownload, $yesterdayDownload])
            ->filter(fn(Carbon $downloadDate) => $downloadDate->greaterThanOrEqualTo($now->copy()->startOfWeek()))
            ->count();

        $this->assertSame((string)$weeklyCount, $stats[1]->getValue());
        $averageDaysQuery = Assignment::query()
            ->where('channel_id', $channel->getKey())
            ->where('status', StatusEnum::PICKEDUP->value)
            ->whereHas('downloads')
            ->join('downloads', 'assignments.id', '=', 'downloads.assignment_id');

        $calculatedAverage = (float)$averageDaysQuery
            ->selectRaw("AVG((strftime('%s', 'now') - strftime('%s', downloads.downloaded_at)) / 86400) as avg_days_ago")
            ->value('avg_days_ago');

        $this->assertSame((string)(int)round($calculatedAverage ?: 0.0), $stats[2]->getValue());
        $this->assertSame($expectedChart, $chartData);
        $this->assertSame($chartData, $stats[0]->getChart());
    }

    private function callProtectedMethod(object $object, string $method, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $parameters);
    }
}
