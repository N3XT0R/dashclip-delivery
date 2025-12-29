<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Clusters\ChannelWorkspace\Resources\ChannelResource\Pages\EditChannel;
use App\Models\Channel;
use App\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class EditChannelTest extends DatabaseTestCase
{
    public function testViewActionVisibleForAuthorizedUser(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()->admin(GuardEnum::STANDARD)->create();

        $this->grantChannelPermissions($user, ['ViewAny:Channel', 'View:Channel', 'Update:Channel']);
        $user->channels()->attach($channel->getKey(), ['is_user_verified' => true]);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::auth()->login($user);
        Livewire::actingAs($user, GuardEnum::STANDARD->value);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(EditChannel::class, ['record' => $channel->getKey()])
            ->assertStatus(200)
            ->assertActionVisible('view');
    }

    public function testUnauthorizedUserCannotAccessEditPage(): void
    {
        $channel = Channel::factory()->create();
        $user = User::factory()->standard()->create();

        $user->channels()->attach($channel->getKey(), ['is_user_verified' => true]);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::auth()->login($user);
        Livewire::actingAs($user, GuardEnum::STANDARD->value);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        Livewire::test(EditChannel::class, ['record' => $channel->getKey()])
            ->assertForbidden();
    }

    private function grantChannelPermissions(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $user->givePermissionTo($permissions);
    }
}
