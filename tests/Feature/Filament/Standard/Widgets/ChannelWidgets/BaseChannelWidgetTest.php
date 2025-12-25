<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Widgets\ChannelWidgets;

use App\Filament\Standard\Widgets\ChannelWidgets\AvailableOffersStatsWidget;
use App\Models\Channel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\DatabaseTestCase;

final class BaseChannelWidgetTest extends DatabaseTestCase
{
    public function testMountStoresProvidedChannelId(): void
    {
        $channel = Channel::factory()->create();
        $widget = new AvailableOffersStatsWidget();

        $widget->mount($channel->getKey());

        $this->assertSame($channel->getKey(), $widget->channelId);
    }

    public function testGetChannelUsesInjectedIdentifier(): void
    {
        $channel = Channel::factory()->create();
        $widget = new AvailableOffersStatsWidget();
        $widget->channelId = $channel->getKey();

        $resolvedChannel = $this->callProtectedMethod($widget, 'getChannel');

        $this->assertNotNull($resolvedChannel);
        $this->assertTrue($channel->is($resolvedChannel));
    }

    public function testGetChannelFallsBackToLatestUserChannel(): void
    {
        $user = User::factory()->create();
        $earlierChannel = Channel::factory()->create(['created_at' => now()->subDay()]);
        $latestChannel = Channel::factory()->create();
        $user->channels()->attach($earlierChannel);
        $user->channels()->attach($latestChannel);
        Auth::login($user);

        $widget = new AvailableOffersStatsWidget();

        $resolvedChannel = $this->callProtectedMethod($widget, 'getChannel');

        $this->assertNotNull($resolvedChannel);
        $this->assertTrue($latestChannel->is($resolvedChannel));
    }

    private function callProtectedMethod(object $object, string $method, array $parameters = []): mixed
    {
        $reflection = new \ReflectionClass($object);
        $reflectionMethod = $reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($object, $parameters);
    }
}
