<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources\VideoResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Resources\VideoResource\Pages\EditVideo;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class EditVideoTest extends DatabaseTestCase
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
        $this->grantVideoPermissions();
    }

    public function testEditVideoPageShowsDeleteAction(): void
    {
        $video = Video::factory()
            ->for($this->tenant, 'team')
            ->withClips(1, $this->user)
            ->create();

        Livewire::test(EditVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertActionVisible('delete');
    }

    private function grantVideoPermissions(): void
    {
        $permissions = [
            'ViewAny:Video',
            'View:Video',
            'Update:Video',
            'Delete:Video',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $this->user->givePermissionTo($permissions);
    }
}
