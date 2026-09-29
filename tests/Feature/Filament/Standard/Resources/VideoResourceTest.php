<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Resources;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\ProcessingStatusEnum;
use App\Enum\StatusEnum;
use App\Filament\Standard\Resources\VideoResource\Pages\ListVideos;
use App\Filament\Standard\Resources\VideoResource\Pages\ViewVideo;
use App\Models\Assignment;
use App\Models\Channel;
use App\Enum\Video\DeliveredVersionEnum;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Storage;
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

    public function testTheListShowsTheWishedChannelOfAVideo(): void
    {
        $channel = Channel::factory()->create(['name' => 'Wunschkanal Einsender']);
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $video->clips()->first()->update(['preferred_channel_id' => $channel->getKey()]);

        Livewire::test(ListVideos::class)
            ->assertCanSeeTableRecords([$video])
            ->assertSee('Wunschkanal Einsender');
    }

    public function testDeleteActionSoftDeletesTheVideoAndKeepsItsFile(): void
    {
        Storage::fake('local');
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        Storage::disk('local')->put($video->path, 'video-bytes');

        Livewire::test(ListVideos::class)
            ->callTableAction('delete', $video)
            ->assertHasNoTableActionErrors();

        $this->assertSoftDeleted('videos', ['id' => $video->getKey()]);
        Storage::disk('local')->assertExists($video->path);
    }

    public function testDeleteIsRefusedWhenAnOfferAppearedWhileTheDialogWasOpen(): void
    {
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();

        $component = Livewire::test(ListVideos::class)->mountTableAction('delete', $video);
        Assignment::factory()->forVideo($video)->create([
            'status' => StatusEnum::QUEUED->value,
            'expires_at' => now()->addWeek(),
        ]);
        $component->callMountedTableAction();

        $this->assertDatabaseHas('videos', ['id' => $video->getKey(), 'deleted_at' => null]);
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

    public function testListVideosCanBeSearchedByTextFields(): void
    {
        $matchingVideo = Video::factory()
            ->for($this->tenant, 'team')
            ->withClips(1, $this->user)
            ->create(['original_name' => 'Quarterly Launch.mp4']);

        $otherVideo = Video::factory()
            ->for($this->tenant, 'team')
            ->withClips(1, $this->user)
            ->create(['original_name' => 'Unrelated Video.mp4']);

        $matchingVideo->clips()->firstOrFail()->update([
            'bundle_key' => 'summer-campaign',
            'role' => 'presenter',
        ]);

        Livewire::test(ListVideos::class)
            ->searchTable('Quarterly Launch')
            ->assertCanSeeTableRecords([$matchingVideo])
            ->assertCanNotSeeTableRecords([$otherVideo])
            ->searchTable('summer-campaign')
            ->assertCanSeeTableRecords([$matchingVideo])
            ->assertCanNotSeeTableRecords([$otherVideo])
            ->searchTable('presenter')
            ->assertCanSeeTableRecords([$matchingVideo])
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

    public function testListVideosShowsExpiredQueuedAssignmentAsExpired(): void
    {
        $video = Video::factory()
            ->for($this->tenant, 'team')
            ->withClips(1, $this->user)
            ->create();

        Assignment::factory()
            ->forVideo($video)
            ->withBatch()
            ->state([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => now()->subDay(),
            ])
            ->create();

        Livewire::test(ListVideos::class)
            ->assertCanSeeTableRecords([$video])
            ->assertSeeText('Abgelaufen')
            ->assertDontSeeText('Alle verteilt');
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

    public function testTheSubmitterCanHandOutTheOtherVersion(): void
    {
        $video = $this->videoWithBothVersions();
        $preview = \Mockery::mock(\App\Services\PreviewService::class);
        $preview->shouldReceive('generatePreviewForClip')->andReturn('previews/new.mp4');
        $this->app->instance(\App\Services\PreviewService::class, $preview);

        Livewire::test(ListVideos::class)
            ->assertTableActionVisible('switch-version', $video)
            ->callTableAction('switch-version', $video);

        self::assertSame(DeliveredVersionEnum::ORIGINAL, $video->refresh()->delivered_version);
    }

    public function testAVideoWhoseFilesAreGoneIsNotOfferedTheSwitch(): void
    {
        $video = $this->videoWithBothVersions();
        $video->update(['processing_status' => ProcessingStatusEnum::Deleted]);

        Livewire::test(ListVideos::class)
            ->assertTableActionHidden('switch-version', $video->refresh());
    }

    public function testAVideoWithOneVersionIsNotOfferedTheSwitch(): void
    {
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();

        Livewire::test(ListVideos::class)
            ->assertTableActionHidden('switch-version', $video);
    }

    private function videoWithBothVersions(): Video
    {
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create([
            'path' => 'videos/blurred.mp4',
            'source_path' => 'videos/original.mp4',
            'delivered_version' => DeliveredVersionEnum::BLURRED->value,
        ]);

        return $video->refresh();
    }

    public function testTheDetailsTellTheSubmitterThatThePlatesWereBlurred(): void
    {
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $video->update([
            'source_path' => 'videos/original.mp4',
            'delivered_version' => DeliveredVersionEnum::BLURRED->value,
        ]);

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertSee(__('filament.video_resource.view.fields.blurred_state'));
    }

    public function testTheDetailsOfAnUntouchedVideoSayNothingAboutBlurring(): void
    {
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();

        Livewire::test(ViewVideo::class, ['record' => $video->getKey()])
            ->assertStatus(200)
            ->assertDontSee(__('filament.video_resource.view.fields.blurred_state'));
    }

    public function testAVideoWhoseDistributionIsFinishedStaysVisibleWithItsState(): void
    {
        $finished = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)
            ->create(['original_name' => 'Finished Clip.mp4']);
        $finished->delete();

        Livewire::test(ListVideos::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$finished])
            ->assertSee('Finished Clip.mp4')
            ->assertSee(__('status.distribution_status.finished'));
    }

    public function testAFinishedVideoOffersNoDeleteAction(): void
    {
        $finished = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $finished->delete();

        Livewire::test(ListVideos::class)
            ->assertTableActionHidden('delete', $finished);
    }
}
