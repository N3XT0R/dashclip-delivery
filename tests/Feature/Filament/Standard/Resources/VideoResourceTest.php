<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\StatusEnum;
use App\Filament\Standard\Resources\VideoResource\Pages\ListVideos;
use App\Models\Assignment;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class VideoResourceTest extends DatabaseTestCase
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

    public function testListVideosShowsOnlyAuthenticatedUsersRecords(): void
    {
        $ownVideo = Video::factory()
            ->for($this->tenant, 'team')
            ->withClips(1, $this->user)
            ->create(['original_name' => 'My Clip.mp4']);

        $otherVideo = Video::factory()
            ->withClips(1)
            ->create(['original_name' => 'Other Clip.mp4']);

        Livewire::test(ListVideos::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$ownVideo])
            ->assertCanNotSeeTableRecords([$otherVideo]);
    }

    public function testAssignmentStateFilterKeepsOnlyActiveOffers(): void
    {
        $activeVideo = Video::factory()
            ->for($this->tenant, 'team')
            ->withClips(1, $this->user)
            ->create();

        $expiredVideo = Video::factory()
            ->for($this->tenant, 'team')
            ->withClips(1, $this->user)
            ->create();

        Assignment::factory()
            ->forVideo($activeVideo)
            ->withBatch()
            ->state(['status' => StatusEnum::QUEUED->value, 'expires_at' => now()->addDay()])
            ->create();

        Assignment::factory()
            ->forVideo($expiredVideo)
            ->withBatch()
            ->state(['status' => StatusEnum::EXPIRED->value, 'expires_at' => now()->subDay()])
            ->create();

        Livewire::test(ListVideos::class)
            ->set('tableFilters.assignment_state.value', 'active')
            ->assertCanSeeTableRecords([$activeVideo])
            ->assertCanNotSeeTableRecords([$expiredVideo]);
    }

    public function testListVideosShowsStatusAndAssignmentCounts(): void
    {
        $video = Video::factory()
            ->for($this->tenant, 'team')
            ->withClips(1, $this->user)
            ->create();

        Assignment::factory()
            ->forVideo($video)
            ->withBatch()
            ->state(['status' => StatusEnum::PICKEDUP->value])
            ->create();

        Assignment::factory()
            ->forVideo($video)
            ->withBatch()
            ->state(['status' => StatusEnum::EXPIRED->value])
            ->create();

        Livewire::test(ListVideos::class)
            ->assertStatus(200)
            ->assertTableColumnExists('status_label')
            ->assertTableColumnExists('available_assignments_count')
            ->assertTableColumnExists('expired_assignments_count')
            ->assertSeeText('Heruntergeladen');
    }

    private function grantVideoPermissions(): void
    {
        $permissions = [
            'ViewAny:Video',
            'View:Video',
            'Create:Video',
            'Update:Video',
            'Delete:Video',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, GuardEnum::STANDARD->value);
        }

        $this->user->givePermissionTo($permissions);
    }
}
