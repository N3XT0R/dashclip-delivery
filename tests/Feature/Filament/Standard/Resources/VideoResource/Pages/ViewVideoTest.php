<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources\VideoResource\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\StatusEnum;
use App\Filament\Standard\Resources\VideoResource\Pages\ViewVideo;
use App\Models\Assignment;
use App\Models\Clip;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class ViewVideoTest extends DatabaseTestCase
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

    public function testViewVideoShowsFormattedDurationAndStatus(): void
    {
        $video = Video::factory()
            ->for($this->tenant, 'team')
            ->create(['original_name' => 'Awesome Clip.mp4']);

        Clip::factory()
            ->for($video)
            ->forUser($this->user)
            ->range(0, 65)
            ->create(['bundle_key' => 'bundle-123']);

        Assignment::factory()
            ->forVideo($video)
            ->withBatch()
            ->state([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => now()->addDays(2),
            ])
            ->create();

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSeeText('Awesome Clip.mp4')
            ->assertSeeText('1:05')
            ->assertSeeText('Verfügbar');
    }

    private function grantVideoPermissions(): void
    {
        $permissions = [
            'ViewAny:Video',
            'View:Video',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $this->user->givePermissionTo($permissions);
    }
}
