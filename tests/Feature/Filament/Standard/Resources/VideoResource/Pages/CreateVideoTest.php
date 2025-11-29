<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources\VideoResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Resources\VideoResource\Pages\CreateVideo;
use App\Models\User;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class CreateVideoTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()
            ->withOwnTeam()
            ->admin(GuardEnum::STANDARD)
            ->create();

        $tenant = app(TeamRepository::class)->getDefaultTeamForUser($user);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($tenant, true);
        Filament::auth()->login($user);
        $this->actingAs($user, GuardEnum::STANDARD->value);

        $this->grantVideoPermissions($user);
    }

    public function testCreateVideoPageIsAccessible(): void
    {
        Livewire::test(CreateVideo::class)
            ->assertStatus(200);
    }

    private function grantVideoPermissions(User $user): void
    {
        $permissions = [
            'ViewAny:Video',
            'Create:Video',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $user->givePermissionTo($permissions);
    }
}
