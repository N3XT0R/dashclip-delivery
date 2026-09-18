<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources\ChannelTeamResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Resources\ChannelTeamResource\Pages\EditChannelTeam;
use App\Models\Channel;
use App\Models\Pivots\ChannelTeamPivot;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class EditChannelTeamTest extends DatabaseTestCase
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

    public function testEditChannelTeamUpdatesQuota(): void
    {
        $channel = Channel::factory()->create();

        $channelTeam = ChannelTeamPivot::query()->create([
            'team_id' => $this->tenant->getKey(),
            'channel_id' => $channel->getKey(),
            'quota' => 6,
        ]);

        Livewire::test(EditChannelTeam::class, ['record' => $channelTeam->getKey()])
            ->assertStatus(200)
            ->assertFormSet([
                'channel_id' => $channel->getKey(),
                'quota' => 6,
            ])
            ->fillForm(['quota' => 11])
            ->call('save')
            ->assertHasNoFormErrors();

        $channelTeam->refresh();

        $this->assertSame(11, $channelTeam->quota);
    }
}
