<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Clusters\ChannelWorkspace\ChannelWorkspace;
use App\Filament\Standard\Clusters\ChannelWorkspace\Pages\ChannelOAuthSettings;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use App\Repository\TeamRepository;
use Tests\DatabaseTestCase;

final class ChannelOAuthSettingsTest extends DatabaseTestCase
{
    public function testChannelOauthSettingsIsNotAccessible(): void
    {
        $user = User::factory()
            ->withOwnTeam()
            ->withRole(RoleEnum::CHANNEL_OPERATOR, GuardEnum::STANDARD->value)
            ->create();

        $tenant = app(TeamRepository::class)->getDefaultTeamForUser($user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($tenant, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(ChannelOAuthSettings::class)
            ->assertForbidden();
    }

    public function testClusterAndViewAreRegistered(): void
    {
        $this->assertSame(ChannelWorkspace::class, ChannelOAuthSettings::getCluster());
        $this->assertSame(
            'filament.standard.pages.channel-o-auth-settings',
            (new ChannelOAuthSettings())->getView(),
        );
    }
}
