<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Widgets\ChannelWidgets;

use App\Enum\StatusEnum;
use App\Filament\Standard\Widgets\ChannelWidgets\ExpiredOffersStatsWidget;
use App\Models\Assignment;
use App\Models\Channel;
use Carbon\Carbon;
use Tests\DatabaseTestCase;

final class ExpiredOffersStatsWidgetTest extends DatabaseTestCase
{
    public function testReturnsZeroStatsWhenChannelUnavailable(): void
    {
        $widget = new ExpiredOffersStatsWidget();

        $stats = $this->callProtectedMethod($widget, 'getStats');

        $this->assertCount(3, $stats);
        $this->assertSame(0, $stats[0]->getValue());
        $this->assertSame(0, $stats[1]->getValue());
        $this->assertSame(0, $stats[2]->getValue());
    }

    public function testComputesExpiredOfferStatistics(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 8, 12));

        $channel = Channel::factory()->create();

        $expiredToday = Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::EXPIRED->value,
            'updated_at' => now(),
        ]);

        $expiredWithDownload = Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::EXPIRED->value,
            'updated_at' => now()->subDays(3),
        ]);
        $expiredWithDownload->downloads()->create([
            'downloaded_at' => now()->subDays(3),
            'ip' => '127.0.0.1',
            'user_agent' => 'test-agent',
            'bytes_sent' => 1,
        ]);

        Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::EXPIRED->value,
            'updated_at' => now()->subDays(6),
        ]);

        $widget = new ExpiredOffersStatsWidget();
        $widget->channelId = $channel->getKey();

        $stats = $this->callProtectedMethod($widget, 'getStats');
        $chartData = $this->callProtectedMethod($widget, 'getExpiredChartData', [$channel]);

        $this->assertSame('3', $stats[0]->getValue());
        $this->assertSame('1', $stats[1]->getValue());
        $this->assertSame('2', $stats[2]->getValue());
        $this->assertSame([1, 0, 0, 1, 0, 0, 1], $chartData);
        $this->assertSame($chartData, $stats[0]->getChart());

        Carbon::setTestNow();
    }

    private function callProtectedMethod(object $object, string $method, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $parameters);
    }
}
