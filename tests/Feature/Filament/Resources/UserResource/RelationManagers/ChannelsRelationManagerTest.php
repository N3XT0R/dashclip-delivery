<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources\UserResource\RelationManagers;

use App\Application\Channel\Application\RevokeChannelAccess;
use App\Enum\Guard\GuardEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Resources\UserResource\Pages\EditUser;
use App\Filament\Resources\UserResource\RelationManagers\ChannelsRelationManager;
use App\Models\Channel;
use App\Models\User;
use App\Repository\ChannelRepository;
use App\Repository\RoleRepository;
use App\Services\Channel\ChannelOperatorService;
use Livewire\Livewire;
use Mockery;
use Tests\DatabaseTestCase;

final class ChannelsRelationManagerTest extends DatabaseTestCase
{
    private User $admin;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->user = User::factory()->create();

        $this->actingAs($this->admin, GuardEnum::DEFAULT->value);
    }

    public function testChannelsTableDisplaysChannelDetails(): void
    {
        $channel = Channel::factory()->create();
        $this->user->channels()->attach($channel->getKey(), [
            'is_user_verified' => true,
        ]);

        Livewire::test(ChannelsRelationManager::class, [
            'ownerRecord' => $this->user,
            'pageClass' => EditUser::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$channel])
            ->assertTableColumnExists('name', record: $channel)
            ->assertTableColumnExists('email', record: $channel)
            ->assertTableColumnExists('pivot.is_user_verified', record: $channel)
            ->assertTableColumnStateSet('name', $channel->name, record: $channel)
            ->assertTableColumnStateSet('email', $channel->email, record: $channel);
    }

    public function testRevokeAccessActionIsVisibleForSuperAdmin(): void
    {
        $channel = Channel::factory()->create();
        $this->user->channels()->attach($channel->getKey());

        Livewire::test(ChannelsRelationManager::class, [
            'ownerRecord' => $this->user,
            'pageClass' => EditUser::class,
        ])
            ->assertSuccessful()
            ->assertTableActionVisible('revokeAccess', $channel);
    }

    public function testRevokeAccessActionHiddenWhenRoleRepositoryDenies(): void
    {
        $channel = Channel::factory()->create();
        $this->user->channels()->attach($channel->getKey());

        $roleRepository = Mockery::mock(RoleRepository::class);
        $roleRepository
            ->shouldReceive('hasRole')
            ->with($this->admin, RoleEnum::SUPER_ADMIN, GuardEnum::DEFAULT)
            ->andReturnFalse();

        app()->instance(RoleRepository::class, $roleRepository);

        Livewire::test(ChannelsRelationManager::class, [
            'ownerRecord' => $this->user,
            'pageClass' => EditUser::class,
        ])
            ->assertSuccessful()
            ->assertTableActionHidden('revokeAccess', $channel);
    }

    public function testRevokeAccessActionCallsHandler(): void
    {
        $channel = Channel::factory()->create();
        $this->user->channels()->attach($channel->getKey());

        $operatorService = new readonly class (
            Mockery::mock(RoleRepository::class),
            Mockery::mock(ChannelRepository::class),
        ) extends ChannelOperatorService {
            public \ArrayObject $calls;

            public function __construct(RoleRepository $roleRepository, ChannelRepository $channelRepository)
            {
                parent::__construct($roleRepository, $channelRepository);
                $this->calls = new \ArrayObject();
            }

            public function revokeUserChannelAccess(User $user, Channel $channel): void
            {
                $this->calls->append([$user, $channel]);
            }
        };

        app()->instance(ChannelOperatorService::class, $operatorService);
        app()->forgetInstance(RevokeChannelAccess::class);

        Livewire::test(ChannelsRelationManager::class, [
            'ownerRecord' => $this->user,
            'pageClass' => EditUser::class,
        ])
            ->callTableAction('revokeAccess', $channel);

        $this->assertCount(1, $operatorService->calls);
        [$user, $revokedChannel] = $operatorService->calls[0];

        $this->assertFalse($user->is($this->admin));
        $this->assertTrue($revokedChannel->is($channel));
    }
}
