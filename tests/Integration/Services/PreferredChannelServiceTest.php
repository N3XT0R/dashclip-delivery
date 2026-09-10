<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Models\Channel;
use App\Services\PreferredChannelService;
use Tests\DatabaseTestCase;

class PreferredChannelServiceTest extends DatabaseTestCase
{
    private PreferredChannelService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PreferredChannelService::class);
    }

    public function testEmptyValueResolvesToNull(): void
    {
        $this->assertNull($this->service->resolveRawValue(''));
        $this->assertNull($this->service->resolveRawValue('   '));
    }

    public function testNumericValueResolvesById(): void
    {
        $channel = Channel::factory()->create();

        $this->assertSame($channel->getKey(), $this->service->resolveRawValue((string) $channel->getKey()));
    }

    public function testNumericValueForPausedChannelResolvesToNull(): void
    {
        $channel = Channel::factory()->paused()->create();

        $this->assertNull($this->service->resolveRawValue((string) $channel->getKey()));
    }

    public function testUnknownNumericIdResolvesToNull(): void
    {
        $this->assertNull($this->service->resolveRawValue('999999'));
    }

    public function testNameResolvesCaseInsensitively(): void
    {
        $channel = Channel::factory()->create(['name' => 'Highway West']);

        $this->assertSame($channel->getKey(), $this->service->resolveRawValue(' highway WEST '));
    }

    public function testNameForPausedChannelResolvesToNull(): void
    {
        Channel::factory()->paused()->create(['name' => 'Paused Lane']);

        $this->assertNull($this->service->resolveRawValue('Paused Lane'));
    }

    public function testUnknownNameResolvesToNull(): void
    {
        $this->assertNull($this->service->resolveRawValue('No Such Channel'));
    }

    public function testPreloadForVideosReturnsUniquePreferencePerVideo(): void
    {
        $channel = \App\Models\Channel::factory()->create();
        $video = \App\Models\Video::factory()->create();
        \App\Models\Clip::factory()->count(2)->for($video)->create(['preferred_channel_id' => $channel->getKey()]);

        $map = $this->service->preloadForVideos(collect([$video]));

        $this->assertSame([$video->getKey() => $channel->getKey()], $map);
    }

    public function testPreloadForVideosOmitsVideosWithConflictingPreferences(): void
    {
        $a = \App\Models\Channel::factory()->create();
        $b = \App\Models\Channel::factory()->create();
        $video = \App\Models\Video::factory()->create();
        \App\Models\Clip::factory()->for($video)->create(['preferred_channel_id' => $a->getKey()]);
        \App\Models\Clip::factory()->for($video)->create(['preferred_channel_id' => $b->getKey()]);

        $map = $this->service->preloadForVideos(collect([$video]));

        $this->assertArrayNotHasKey($video->getKey(), $map);
    }

    public function testResolveForGroupReturnsSharedPreference(): void
    {
        $v1 = \App\Models\Video::factory()->create();
        $v2 = \App\Models\Video::factory()->create();
        $map = [$v1->getKey() => 7, $v2->getKey() => 7];

        $this->assertSame(7, $this->service->resolveForGroup(collect([$v1, $v2]), $map));
    }

    public function testResolveForGroupReturnsNullOnDisagreement(): void
    {
        $v1 = \App\Models\Video::factory()->create();
        $v2 = \App\Models\Video::factory()->create();
        $map = [$v1->getKey() => 7, $v2->getKey() => 9];

        $this->assertNull($this->service->resolveForGroup(collect([$v1, $v2]), $map));
    }

    public function testResolveForGroupReturnsNullWhenNoPreference(): void
    {
        $v1 = \App\Models\Video::factory()->create();

        $this->assertNull($this->service->resolveForGroup(collect([$v1]), []));
    }

    public function testResolveForGroupIgnoresVideosWithoutPreference(): void
    {
        $v1 = \App\Models\Video::factory()->create();
        $v2 = \App\Models\Video::factory()->create();
        $map = [$v1->getKey() => 7];

        $this->assertSame(7, $this->service->resolveForGroup(collect([$v1, $v2]), $map));
    }
}
