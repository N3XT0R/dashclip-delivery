<?php

declare(strict_types=1);

namespace Tests\Feature\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Pages\MyOffers;
use App\Models\Channel;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\DatabaseTestCase;

final class MyOffersTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('auth.defaults.guard', GuardEnum::STANDARD->value);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
    }

    public function testChannelOperatorCanAccessPage(): void
    {
        $user = User::factory()->create();
        Role::findOrCreate(RoleEnum::CHANNEL_OPERATOR->value, GuardEnum::STANDARD->value);
        $user->syncRoles([RoleEnum::CHANNEL_OPERATOR->value]);

        $team = app(TeamRepository::class)->createOwnTeamForUser($user);

        $channel = Channel::factory()->create();
        $channel->channelUsers()->attach($user);

        Filament::setTenant($team, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(MyOffers::class)
            ->assertStatus(200);
    }
}
