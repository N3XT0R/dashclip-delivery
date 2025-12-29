<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources;

use App\Enum\Guard\GuardEnum;
use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers\UsersRelationManager;
use App\Models\Channel;
use App\Models\User;
use Filament\Schemas\Schema;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class ChannelResourceTest extends DatabaseTestCase
{
    public function testNavigationAndLabels(): void
    {
        self::assertSame(ChannelWorkspace::class, ChannelResource::getCluster());
        self::assertSame(__('common.channel'), ChannelResource::getRecordTitleAttribute());
    }

    public function testFormSchemaHasExpectedComponents(): void
    {
        $schema = ChannelResource::form(Schema::make());
        $components = Collection::make($schema->getComponents());

        self::assertSame([
            'name',
            'creator_name',
            'email',
            'youtube_name',
            'is_video_reception_paused',
        ], $components->map(fn($component) => $component->getName())->all());

        $components->each(function ($component): void {
            $this->assertTrue($component->isVisible());
        });
    }

    public function testInfolistHasExpectedEntries(): void
    {
        $schema = ChannelResource::infolist(Schema::make());
        $components = Collection::make($schema->getComponents());

        self::assertSame([
            'name',
            'email',
            'youtube_name',
            'is_video_reception_paused',
            'created_at',
            'updated_at',
        ], $components->map(fn($component) => $component->getName())->all());
    }

    public function testCanCreateIsDisabledAndRelationsAreDefined(): void
    {
        self::assertFalse(ChannelResource::canCreate());
        self::assertSame([UsersRelationManager::class], ChannelResource::getRelations());
    }

    public function testPagesConfigurationIsRegistered(): void
    {
        $pages = ChannelResource::getPages();

        self::assertSame(Pages\ListChannels::class, $pages['index']->getPage());
        self::assertSame(Pages\ViewChannel::class, $pages['view']->getPage());
        self::assertSame(Pages\EditChannel::class, $pages['edit']->getPage());
    }

    public function testEloquentQueryReturnsChannelsUserCanAccess(): void
    {
        $user = User::factory()->create();
        $grantedChannel = Channel::factory()->create();
        $hiddenChannel = Channel::factory()->create();

        $this->grantChannelPermissions($user, ['ViewAny:Channel', 'View:Channel']);
        $user->channels()->attach($grantedChannel->getKey(), ['is_user_verified' => true]);

        $this->actingAs($user, GuardEnum::STANDARD->value);

        $channelIds = ChannelResource::getEloquentQuery()->pluck('id');

        self::assertTrue($channelIds->contains($grantedChannel->getKey()));
        self::assertFalse($channelIds->contains($hiddenChannel->getKey()));
    }

    private function grantChannelPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $user->givePermissionTo($permissions);
    }
}
