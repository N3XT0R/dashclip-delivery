<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Auth\Abilities\AccessChannelPageAbility;
use App\Auth\Abilities\Contracts\AbilityContract;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages\ViewChannel;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers\UsersRelationManager;
use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use App\Repository\TeamRepository;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;
use Illuminate\Support\Facades\Gate;

final class UsersRelationManagerTest extends DatabaseTestCase
{
    private User $user;

    private Channel $channel;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('filament-shield.enabled', false);

        $this->channel = Channel::factory()->create();
        $this->allowChannelAccessAbility();
        $this->allowChannelGate();
        $this->user = User::factory()
            ->withOwnTeam()
            ->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)
            ->haveAccessToChannel($this->channel)
            ->create();

        $this->grantChannelPermissions();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
        $this->actingAs($this->user);
    }

    public function testUsersRelationManagerListsMembersAndHidesSelfRevokeAction(): void
    {
        $otherUser = User::factory()
            ->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)
            ->haveAccessToChannel($this->channel)
            ->create();

        Livewire::test(UsersRelationManager::class, [
            'ownerRecord' => $this->channel,
            'pageClass' => ViewChannel::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$this->user, $otherUser])
            ->assertTableActionHidden('revokeAccess', $this->user)
            ->assertTableActionVisible('revokeAccess', $otherUser);
    }

    private function allowChannelAccessAbility(): void
    {
        $this->app->bind(AccessChannelPageAbility::class, static fn(): AbilityContract => new class () implements AbilityContract {
            public function check(User $user): bool
            {
                return true;
            }

            public function checkForChannel(User $user, ?Channel $channel = null): bool
            {
                return true;
            }
        });
    }

    private function allowChannelGate(): void
    {
        Gate::after(static function (User $user, string $ability): ?bool {
            if (in_array($ability, ['page.channels.access', 'page.channels.access_for_channel'], true)) {
                return true;
            }

            return null;
        });
    }

    private function grantChannelPermissions(): void
    {
        $permissions = [
            'ViewAny:Channel',
            'View:Channel',
            'Update:Channel',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $this->user->givePermissionTo($permissions);
    }
}
