<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources\VideoResource\RelationManagers;

use App\Enum\BatchTypeEnum;
use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\StatusEnum;
use App\Filament\Standard\Resources\VideoResource\Pages\ViewVideo;
use App\Filament\Standard\Resources\VideoResource\RelationManagers\AssignmentsRelationManager;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Clip;
use App\Models\Download;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\DatabaseTestCase;

final class AssignmentsRelationManagerTest extends DatabaseTestCase
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

    public function testDownloadStateAndStatusAreRenderedInTable(): void
    {
        $video = Video::factory()
            ->for($this->tenant, 'team')
            ->create();

        Clip::factory()
            ->for($video)
            ->forUser($this->user)
            ->create();

        $batch = Batch::factory()
            ->type(BatchTypeEnum::ASSIGN->value)
            ->create();

        $pendingChannel = Channel::factory()->create();
        $rejectedChannel = Channel::factory()->create();
        $downloadedChannel = Channel::factory()->create();

        $pendingAssignment = Assignment::factory()
            ->forVideo($video)
            ->forChannel($pendingChannel)
            ->withBatch($batch)
            ->create([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => Carbon::parse('2030-01-02 10:00:00'),
            ]);

        $rejectedAssignment = Assignment::factory()
            ->forVideo($video)
            ->forChannel($rejectedChannel)
            ->withBatch($batch)
            ->create([
                'status' => StatusEnum::REJECTED->value,
            ]);

        $downloadedAssignment = Assignment::factory()
            ->forVideo($video)
            ->forChannel($downloadedChannel)
            ->withBatch($batch)
            ->create([
                'status' => StatusEnum::PICKEDUP->value,
            ]);

        Download::factory()
            ->forAssignment($downloadedAssignment)
            ->at(Carbon::parse('2030-04-05 15:30:00'))
            ->create();

        Livewire::test(AssignmentsRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => ViewVideo::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([
                $pendingAssignment,
                $rejectedAssignment,
                $downloadedAssignment,
            ])
            ->assertTableColumnStateSet('status', StatusEnum::QUEUED->value, record: $pendingAssignment)
            ->assertTableColumnStateSet('status', StatusEnum::REJECTED->value, record: $rejectedAssignment)
            ->assertTableColumnStateSet('status', StatusEnum::PICKEDUP->value, record: $downloadedAssignment)
            ->assertTableColumnStateSet('download_state', 'Noch nicht heruntergeladen', record: $pendingAssignment)
            ->assertTableColumnStateSet('download_state', 'Zurückgegeben', record: $rejectedAssignment)
            ->assertTableColumnStateSet('download_state', 'Heruntergeladen am 05.04.2030 15:30', record: $downloadedAssignment);
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
