<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Pages\DownloadHistory;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Download;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class DownloadHistoryTest extends DatabaseTestCase
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
    }

    public function testShowsOnlyDownloadsOfAuthenticatedUsersVideos(): void
    {
        $ownVideo = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $ownAssignment = Assignment::factory()->forVideo($ownVideo)->create();
        $ownDownload = Download::factory()->forAssignment($ownAssignment)->create();

        $otherVideo = Video::factory()->withClips(1)->create();
        $otherAssignment = Assignment::factory()->forVideo($otherVideo)->create();
        $otherDownload = Download::factory()->forAssignment($otherAssignment)->create();

        Livewire::test(DownloadHistory::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$ownDownload])
            ->assertCanNotSeeTableRecords([$otherDownload]);
    }

    public function testTableIsSortedByDownloadedAtDescending(): void
    {
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();

        $olderAssignment = Assignment::factory()->forVideo($video)->create();
        $olderDownload = Download::factory()
            ->forAssignment($olderAssignment)
            ->at(now()->subDays(2))
            ->create();

        $newerAssignment = Assignment::factory()->forVideo($video)->create();
        $newerDownload = Download::factory()
            ->forAssignment($newerAssignment)
            ->at(now())
            ->create();

        Livewire::test(DownloadHistory::class)
            ->assertCanSeeTableRecords([$newerDownload, $olderDownload], inOrder: true);
    }

    public function testShowsChannelNameThatDownloadedTheVideo(): void
    {
        $channel = Channel::factory()->create(['name' => 'Awesome Channel']);
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $assignment = Assignment::factory()->forVideo($video)->forChannel($channel)->create();
        Download::factory()->forAssignment($assignment)->create();

        Livewire::test(DownloadHistory::class)
            ->assertSee('Awesome Channel');
    }

    public function testShowsEmptyStateWhenNoDownloadsExist(): void
    {
        Livewire::test(DownloadHistory::class)
            ->assertSee(__('download_history.table.empty_state.heading'));
    }

    public function testNavigationTextsUseTranslations(): void
    {
        self::assertSame(
            __('download_history.title'),
            (new DownloadHistory())->getTitle()
        );
        self::assertSame(
            __('download_history.navigation_label'),
            DownloadHistory::getNavigationLabel()
        );
        self::assertSame(
            __('nav.media'),
            DownloadHistory::getNavigationGroup()
        );
    }
}
