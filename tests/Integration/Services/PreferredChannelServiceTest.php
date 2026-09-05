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
}
