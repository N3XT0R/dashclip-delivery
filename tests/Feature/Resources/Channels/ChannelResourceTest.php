<?php

declare(strict_types=1);

namespace Tests\Feature\Resources\Channels;

use App\Filament\Resources\Channels\ChannelResource;
use App\Filament\Resources\Channels\Pages\CreateChannel;
use App\Filament\Resources\Channels\Pages\EditChannel;
use App\Filament\Resources\Channels\Pages\ListChannels;
use App\Models\Channel;
use App\Models\User;
use Filament\Resources\Pages\PageRegistration;
use Tests\DatabaseTestCase;

final class ChannelResourceTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin, 'web');
    }

    public function testResourceMetadataMatchesChannelModel(): void
    {
        $this->assertSame(Channel::class, ChannelResource::getModel());
        $this->assertSame('heroicon-o-envelope', ChannelResource::getNavigationIcon());
        $this->assertSame('Media', ChannelResource::getNavigationGroup());
        $this->assertSame('Channel', ChannelResource::getModelLabel());
        $this->assertSame('Channels', ChannelResource::getPluralModelLabel());
    }

    public function testResourceRegistersExpectedPages(): void
    {
        $pages = ChannelResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('create', $pages);
        $this->assertArrayHasKey('edit', $pages);

        $this->assertInstanceOf(PageRegistration::class, $pages['index']);
        $this->assertInstanceOf(PageRegistration::class, $pages['create']);
        $this->assertInstanceOf(PageRegistration::class, $pages['edit']);

        $this->assertSame(ListChannels::class, $pages['index']->getPage());
        $this->assertSame(CreateChannel::class, $pages['create']->getPage());
        $this->assertSame(EditChannel::class, $pages['edit']->getPage());
    }
}
