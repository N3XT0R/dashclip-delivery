<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources\ChannelTeamResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Resources\ChannelTeamResource\Pages\CreateChannelTeam;
use App\Models\Channel;
use App\Models\Pivots\ChannelTeamPivot;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class CreateChannelTeamTest extends DatabaseTestCase
{
    private User $user;

    private Team $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->admin(GuardEnum::STANDARD)
            ->create();

        $this->tenant = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->tenant, true);
        Filament::auth()->login($this->user);
        $this->actingAs($this->user, GuardEnum::STANDARD->value);
    }

    public function testCreateChannelTeamStoresRecordForCurrentTenant(): void
    {
        $channel = Channel::factory()->create();

        Livewire::test(CreateChannelTeam::class)
            ->assertStatus(200)
            ->fillForm([
                'channel_id' => $channel->getKey(),
                'quota' => 9,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas(ChannelTeamPivot::class, [
            'team_id' => $this->tenant->getKey(),
            'channel_id' => $channel->getKey(),
            'quota' => 9,
        ]);
    }

    public function testPausedChannelIsNotOfferedForSelection(): void
    {
        $active = Channel::factory()->create(['name' => 'Active Channel']);
        $paused = Channel::factory()->create(['name' => 'Paused Channel', 'is_video_reception_paused' => true]);

        $options = Livewire::test(CreateChannelTeam::class)
            ->instance()
            ->getSchema('form')
            ->getComponent('channel_id')
            ->getSearchResults('Channel');

        $this->assertArrayHasKey($active->getKey(), $options);
        $this->assertArrayNotHasKey($paused->getKey(), $options);
    }

    public function testPausedChannelCannotBeAssigned(): void
    {
        $channel = Channel::factory()->create(['is_video_reception_paused' => true]);

        Livewire::test(CreateChannelTeam::class)
            ->fillForm([
                'channel_id' => $channel->getKey(),
                'quota' => 9,
            ])
            ->call('create')
            ->assertHasFormErrors(['channel_id']);

        $this->assertDatabaseMissing(ChannelTeamPivot::class, ['channel_id' => $channel->getKey()]);
    }
}
