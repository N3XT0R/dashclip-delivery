<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Resources\ChannelTeamResource;
use App\Models\Channel;
use App\Models\Pivots\ChannelTeamPivot;
use App\Models\Team;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Tests\DatabaseTestCase;

final class ChannelTeamResourceTest extends DatabaseTestCase
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

    public function testEloquentQueryReturnsOnlyRecordsFromCurrentTenant(): void
    {
        $channel = Channel::factory()->create();
        $tenantChannelTeam = ChannelTeamPivot::query()->create([
            'team_id' => $this->tenant->getKey(),
            'channel_id' => $channel->getKey(),
            'quota' => 8,
        ]);

        $otherTeam = Team::factory()->create();
        $otherChannel = Channel::factory()->create();
        $otherChannelTeam = ChannelTeamPivot::query()->create([
            'team_id' => $otherTeam->getKey(),
            'channel_id' => $otherChannel->getKey(),
            'quota' => 12,
        ]);

        $records = ChannelTeamResource::getEloquentQuery()->get();

        $this->assertTrue($records->contains(fn(ChannelTeamPivot $record) => $record->is($tenantChannelTeam)));
        $this->assertFalse($records->contains(fn(ChannelTeamPivot $record) => $record->is($otherChannelTeam)));
        $this->assertSame([$tenantChannelTeam->getKey()], $records->pluck('id')->all());
    }
}
