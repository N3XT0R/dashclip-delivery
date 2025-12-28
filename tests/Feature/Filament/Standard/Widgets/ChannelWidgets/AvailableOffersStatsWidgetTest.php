<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Widgets\ChannelWidgets;

use App\Enum\StatusEnum;
use App\Filament\Standard\Widgets\ChannelWidgets\AvailableOffersStatsWidget;
use App\Models\Assignment;
use App\Models\Channel;
use Carbon\Carbon;
use Tests\DatabaseTestCase;

final class AvailableOffersStatsWidgetTest extends DatabaseTestCase
{
    public function testReturnsZeroStatWhenChannelUnavailable(): void
    {
        $widget = new AvailableOffersStatsWidget();

        $stats = $this->callProtectedMethod($widget, 'getStats');

        $this->assertCount(1, $stats);
        $this->assertSame(0, $stats[0]->getValue());
        $this->assertNull($stats[0]->getChart());
    }

    public function testProvidesCountsAndChartDataForChannel(): void
    {
        Carbon::setTestNow(Carbon::create(2024, 1, 8, 12));

        $channel = Channel::factory()->create();

        Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::QUEUED->value,
            'expires_at' => now()->addDays(12),
            'created_at' => now()->subDays(6)->startOfDay(),
        ]);

        Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::NOTIFIED->value,
            'expires_at' => now()->addDays(2),
            'created_at' => now()->subDays(2)->startOfDay(),
        ]);

        Assignment::factory()->create([
            'channel_id' => $channel->getKey(),
            'status' => StatusEnum::QUEUED->value,
            'expires_at' => now()->subDay(),
            'created_at' => now()->subDays(4)->startOfDay(),
        ]);

        $widget = new AvailableOffersStatsWidget();
        $widget->channelId = $channel->getKey();

        $stats = $this->callProtectedMethod($widget, 'getStats');
        $chartData = $this->callProtectedMethod($widget, 'getAvailableChartData', [$channel]);

        $this->assertCount(1, $stats);
        $this->assertSame('2', $stats[0]->getValue());
        $this->assertSame([1, 1, 2, 2, 3, 3, 2], $chartData);
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
