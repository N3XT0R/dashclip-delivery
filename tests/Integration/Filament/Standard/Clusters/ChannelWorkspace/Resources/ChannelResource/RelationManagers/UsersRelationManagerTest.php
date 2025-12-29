<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages\ViewChannel;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers\UsersRelationManager;
use App\Models\Channel;
use App\Models\User;
use App\Services\Channel\ChannelOperatorService;
use App\Repository\ChannelRepository;
use App\Repository\RoleRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class UsersRelationManagerTest extends DatabaseTestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function testTitleUsesTranslations(): void
    {
        $channel = Channel::factory()->create();

        self::assertSame(
            __('channel-workspace.channel_resource.relation_manager.users.title'),
            UsersRelationManager::getTitle($channel, ViewChannel::class)
        );
    }

    public function testRevokeAccessActionIsHiddenForSelfAndCallsServiceForOthers(): void
    {
        $channel = Channel::factory()->create();
        $currentUser = User::factory()->standard()->create();
        $otherUser = User::factory()->standard()->create();

        $this->grantChannelPermissions($currentUser, ['View:Channel']);

        $currentUser->channels()->attach($channel->getKey(), ['is_user_verified' => true]);
        $otherUser->channels()->attach($channel->getKey(), ['is_user_verified' => true]);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::auth()->login($currentUser);
        Livewire::actingAs($currentUser, GuardEnum::STANDARD->value);
        $this->actingAs($currentUser, GuardEnum::STANDARD->value);

        $channelRepository = Mockery::mock(ChannelRepository::class);
        $roleRepository = Mockery::mock(RoleRepository::class);

        $channelRepository->shouldReceive('hasUserAccessToChannel')
            ->with($otherUser, $channel)
            ->andReturnTrue();
        $channelRepository->shouldReceive('unassignUserFromChannel')
            ->once()
            ->with($otherUser, $channel);
        $channelRepository->shouldReceive('hasUserAccessToAnyChannel')
            ->with($otherUser)
            ->andReturnFalse();

        $roleRepository->shouldReceive('removeRoleFromUser')
            ->once()
            ->with($otherUser, RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD);

        $this->app->instance(
            ChannelOperatorService::class,
            new ChannelOperatorService($roleRepository, $channelRepository)
        );

        Livewire::test(UsersRelationManager::class, [
            'ownerRecord' => $channel,
            'pageClass' => ViewChannel::class,
        ])
            ->assertCanSeeTableRecords([$currentUser, $otherUser])
            ->assertTableActionHidden('revokeAccess', record: $currentUser)
            ->assertTableActionVisible('revokeAccess', record: $otherUser)
            ->callTableAction('revokeAccess', $otherUser);
    }

    private function grantChannelPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $user->givePermissionTo($permissions);
    }
}
