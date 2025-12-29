<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages;

use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages\ListChannels;
use Tests\TestCase;

final class ListChannelsTest extends TestCase
{
    public function testResourceIsDefined(): void
    {
        $page = new ListChannels();

        self::assertSame(ChannelResource::class, $page::getResource());
    }
}
