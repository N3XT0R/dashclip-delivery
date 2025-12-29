<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages\ListChannels;
use App\Models\Channel;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class ListChannelsTest extends DatabaseTestCase
{
    public function testListChannelsShowsOnlyAccessibleChannels(): void
    {
        $user = User::factory()->admin(GuardEnum::STANDARD)->create();
        $visibleChannel = Channel::factory()->create();
        $hiddenChannel = Channel::factory()->create();

        $this->grantChannelPermissions($user, ['ViewAny:Channel', 'View:Channel']);
        $user->channels()->attach($visibleChannel->getKey(), ['is_user_verified' => true]);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::auth()->login($user);
        Livewire::actingAs($user, GuardEnum::STANDARD->value);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(ListChannels::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$visibleChannel])
            ->assertCanNotSeeTableRecords([$hiddenChannel]);
    }

    private function grantChannelPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $user->givePermissionTo($permissions);
    }
}
