<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Filament\Standard\Pages\SelectChannels;
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
}