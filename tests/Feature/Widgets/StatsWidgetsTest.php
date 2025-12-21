<?php

declare(strict_types=1);

namespace Tests\Feature\Widgets;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Models\Channel;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Tests\DatabaseTestCase;

final class StatsWidgetsTest extends DatabaseTestCase
{
    private User $user;

    private Team $team;

    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->create();

        $this->team = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        $this->channel = Channel::factory()->create();
        $this->channel->assignedTeams()->attach($this->team);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->team, true);
        Filament::auth()->login($this->user);

        $this->actingAs($this->user, GuardEnum::STANDARD->value);

        $this->user->assignRole(RoleEnum::CHANNEL_OPERATOR->value);
    }
}
