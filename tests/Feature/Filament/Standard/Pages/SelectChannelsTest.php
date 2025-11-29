<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Filament\Standard\Pages\SelectChannels;
use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

class SelectChannelsTest extends DatabaseTestCase
{


    protected function setUp(): void
    {
        parent::setUp();
        User::flushEventListeners();
        $guard = GuardEnum::STANDARD;
        $regularUser = User::factory()
            ->withOwnTeam()
            ->standard($guard)
            ->create();
        $tenant = $this->app->make(TeamRepository::class)->getDefaultTeamForUser($regularUser);
        Filament::setTenant($tenant, true);
        $this->actingAs($regularUser, $guard->value);
    }

    public function testRegularUserHasAccess(): void
    {
        Livewire::test(SelectChannels::class)
            ->assertStatus(200);
    }

    public function testTableHasExpectedColumns(): void
    {
        Livewire::test(SelectChannels::class)
            ->assertTableColumnExists('name')
            ->assertTableColumnExists('youtube_name')
            ->assertTableColumnExists('quota');
    }

    public function testOwnerCanAttachChannels(): void
    {
        $user = User::factory()
            ->standard()
            ->withOwnTeam()
            ->standard()
            ->create();

        $tenant = app(TeamRepository::class)->getDefaultTeamForUser($user);

        Filament::setTenant($tenant, true);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        $channels = Channel::factory()->count(3)->create();

        $component = Livewire::test(SelectChannels::class);

        $component->assertActionVisible('attach');

        $component->callAction('attach', [
            'recordId' => $channels->pluck('id')->toArray(),
        ]);

        foreach ($channels as $channel) {
            $this->assertDatabaseHas('channel_team', [
                'team_id' => $tenant->id,
                'channel_id' => $channel->id,
            ]);
        }
    }

    public function testNonOwnerCannotSeeAttachAction(): void
    {
        $user = User::factory()
            ->standard()
            ->withOwnTeam()
            ->create();

        $tenant = Team::factory()->create();

        Filament::setTenant($tenant, true);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(SelectChannels::class)
            ->assertActionHidden('attach');
    }

}