<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Auth\Abilities\AccessChannelPageAbility;
use App\Auth\Abilities\Contracts\AbilityContract;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\RelationManagers\UsersRelationManager;
use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use Filament\Facades\Filament;
use App\Repository\TeamRepository;
use Tests\DatabaseTestCase;

final class ChannelResourceTest extends DatabaseTestCase
{
    private User $user;

    private Channel $channel;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->channel = Channel::factory()->create();
        $this->allowChannelAccessAbility();
        $this->user = User::factory()
            ->withOwnTeam()
            ->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)
            ->haveAccessToChannel($this->channel)
            ->create();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
    }

    public function testChannelsAreScopedToAuthenticatedUser(): void
    {
        $otherChannel = Channel::factory()->create();

        $ids = ChannelResource::getEloquentQuery()->pluck('id')->all();

        $this->assertContains($this->channel->getKey(), $ids);
        $this->assertNotContains($otherChannel->getKey(), $ids);
    }

    public function testMetadataAndRelationsAreRegistered(): void
    {
        $this->assertFalse(ChannelResource::canCreate());
        $this->assertContains(
            UsersRelationManager::class,
            ChannelResource::getRelations(),
        );

        $pages = ChannelResource::getPages();

        $this->assertArrayHasKey('index', $pages);
        $this->assertArrayHasKey('view', $pages);
        $this->assertArrayHasKey('edit', $pages);
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
}
