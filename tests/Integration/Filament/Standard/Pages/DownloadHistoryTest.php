<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Standard\Pages;

use App\Enum\Guard\GuardEnum;
use App\Enum\PanelEnum;
use App\Filament\Standard\Pages\DownloadHistory;
use App\Filament\Standard\Resources\VideoResource;
use App\Models\Assignment;
use App\Models\Channel;
use App\Models\Download;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Repository\TeamRepository;
use Filament\Facades\Filament;
use Filament\Tables\Table;
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

    public function testVideoColumnLinksToVideoViewPage(): void
    {
        [$video, $download] = $this->createDownload();

        $page = app(DownloadHistory::class);
        $table = $page->table(Table::make($page));

        $column = $table->getColumn('assignment.video.original_name');
        $column->record($download);

        $expected = VideoResource::getUrl('view', ['record' => $video]);

        self::assertSame($expected, $column->getUrl());
    }

    public function testViewVideoActionLinksToVideoViewPage(): void
    {
        [$video, $download] = $this->createDownload();

        $page = app(DownloadHistory::class);
        $table = $page->table(Table::make($page));

        $action = $table->getFlatActions()['view-video'];
        $action->record($download);

        $expected = VideoResource::getUrl('view', ['record' => $video]);

        self::assertSame($expected, $action->getUrl());
    }

    /**
     * @return array{0: Video, 1: Download}
     */
    private function createDownload(): array
    {
        $channel = Channel::factory()->create();
        $video = Video::factory()->for($this->tenant, 'team')->withClips(1, $this->user)->create();
        $assignment = Assignment::factory()->forVideo($video)->forChannel($channel)->create();
        $download = Download::factory()->forAssignment($assignment)->create();

        return [$video, $download];
    }
}
