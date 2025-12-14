<?php

declare(strict_types=1);

namespace Tests\Feature\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Enum\StatusEnum;
use App\Enum\Users\RoleEnum;
use App\Filament\Standard\Pages\MyOffers;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Download;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class MyOffersPageTest extends DatabaseTestCase
{
    private User $user;

    private Team $team;

    private Channel $channel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()
            ->withOwnTeam()
            ->create();

        $this->team = app(TeamRepository::class)->getDefaultTeamForUser($this->user);

        $this->channel = Channel::factory()->create();
        $this->channel->assignedTeams()->attach($this->team);

        Filament::setCurrentPanel(PanelEnum::STANDARD->value);
        Filament::setTenant($this->team, true);
        Filament::auth()->login($this->user);

        $this->actingAs($this->user, GuardEnum::STANDARD->value);

        $this->grantChannelOperatorRole();
    }

    private function grantChannelOperatorRole(): void
    {
        $this->user->assignRole(RoleEnum::CHANNEL_OPERATOR->value);
    }

    public function testChannelOperatorCanAccessPage(): void
    {
        $this->assertTrue(MyOffers::canAccess());

        Livewire::test(MyOffers::class)
            ->assertSuccessful();
    }

    public function testNonChannelOperatorCannotAccessPage(): void
    {
        $this->user->removeRole(RoleEnum::CHANNEL_OPERATOR->value);

        $this->assertFalse(MyOffers::canAccess());
    }

    public function testAvailableTabShowsAvailableAssignments(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $availableAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => now()->addDays(5),
            ]);

        $expiredAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::EXPIRED->value,
                'expires_at' => now()->subDay(),
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'available')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$availableAssignment])
            ->assertCanNotSeeTableRecords([$expiredAssignment]);
    }

    public function testDownloadedTabShowsDownloadedAssignments(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $downloadedAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::PICKEDUP->value,
            ]);

        Download::factory()
            ->forAssignment($downloadedAssignment)
            ->at(Carbon::parse('2030-01-15 10:00:00'))
            ->create();

        $notDownloadedAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'downloaded')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$downloadedAssignment])
            ->assertCanNotSeeTableRecords([$notDownloadedAssignment]);
    }

    public function testExpiredTabShowsExpiredAssignments(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $expiredAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::EXPIRED->value,
                'expires_at' => now()->subDay(),
            ]);

        $activeAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => now()->addDay(),
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'expired')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$expiredAssignment])
            ->assertCanNotSeeTableRecords([$activeAssignment]);
    }

    public function testReturnedTabShowsRejectedAssignments(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $rejectedAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::REJECTED->value,
            ]);

        $activeAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'returned')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$rejectedAssignment])
            ->assertCanNotSeeTableRecords([$activeAssignment]);
    }

    public function testAllTabsArePresent(): void
    {
        Livewire::test(MyOffers::class)
            ->assertSuccessful()
            ->assertSee(__('my_offers.tabs.available'))
            ->assertSee(__('my_offers.tabs.downloaded'))
            ->assertSee(__('my_offers.tabs.expired'))
            ->assertSee(__('my_offers.tabs.returned'));
    }

    public function testPageShowsOnlyAssignmentsForCurrentChannel(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $otherChannel = Channel::factory()->create();

        $myAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
            ]);

        $otherAssignment = Assignment::factory()
            ->for($otherChannel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->withClips(1)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'available')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$myAssignment])
            ->assertCanNotSeeTableRecords([$otherAssignment]);
    }

    public function testViewDetailsActionIsPresent(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $assignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'available')
            ->assertSuccessful()
            ->assertTableActionExists('view_details');
    }

    public function testDownloadActionIsPresent(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $assignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'available')
            ->assertSuccessful()
            ->assertTableActionExists('download');
    }

    public function testBulkActionsArePresent(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $assignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'available')
            ->assertSuccessful()
            ->assertTableBulkActionExists('download_selected');
    }

    public function testExpiringAssignmentsAreHighlightedInRed(): void
    {
        $batch = Batch::factory()->type('assign')->finished()->create();

        $expiringAssignment = Assignment::factory()
            ->for($this->channel, 'channel')
            ->withBatch($batch)
            ->forVideo(Video::factory()->for($this->team, 'team')->withClips(1, $this->user)->create())
            ->create([
                'status' => StatusEnum::QUEUED->value,
                'expires_at' => now()->addDays(2), // Less than 3 days
            ]);

        Livewire::test(MyOffers::class)
            ->set('activeTab', 'available')
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$expiringAssignment]);
    }
}
