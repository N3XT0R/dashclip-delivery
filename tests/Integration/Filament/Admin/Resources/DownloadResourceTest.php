<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Downloads\DownloadResource;
use App\Filament\Admin\Resources\Downloads\Pages\ListDownloads;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\Download;
use App\Models\User;
use App\Models\Video;
use Filament\Tables\Table;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class DownloadResourceTest extends DatabaseTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->admin()->create();
        $this->actingAs($this->user);
    }

    public function testListShowsDownloadData(): void
    {
        $channel = Channel::factory()->create(['name' => 'Main Channel']);
        $video = Video::factory()->create();
        $batch = Batch::factory()->type('assign')->create();
        $assignment = Assignment::factory()
            ->forChannel($channel)
            ->forVideo($video)
            ->withBatch($batch)
            ->create(['status' => 'queued']);

        Download::factory()
            ->forAssignment($assignment)
            ->create(['ip' => '203.0.113.1']);

        Livewire::test(ListDownloads::class)
            ->assertStatus(200)
            ->assertSee((string)$assignment->id)
            ->assertSee($assignment->status)
            ->assertSee('203.0.113.1')
            ->assertSee($channel->name)
            ->assertSee((string)$assignment->getAttribute('video')->getAttribute('original_name'));
    }

    public function testListShowsDownloadsOfDeletedVideos(): void
    {
        $video = Video::factory()->create(['original_name' => 'Removed Admin Clip.mp4']);
        $assignment = Assignment::factory()->forVideo($video)->withBatch(Batch::factory()->type('assign')->create())
            ->create(['status' => 'picked_up']);
        $download = Download::factory()->forAssignment($assignment)->create();
        $video->delete();

        Livewire::test(ListDownloads::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords([$download])
            ->assertSee('Removed Admin Clip.mp4')
            ->assertSee(__('filament.admin.labels.deleted'));
    }

    public function testVideoColumnLinksNonDeletedVideoAndHidesLinkForDeletedVideo(): void
    {
        $video = Video::factory()->create();
        $assignment = Assignment::factory()->forVideo($video)->withBatch(Batch::factory()->type('assign')->create())
            ->create();
        $download = Download::factory()->forAssignment($assignment)->create();

        $deletedVideo = Video::factory()->create();
        $deletedAssignment = Assignment::factory()->forVideo($deletedVideo)
            ->withBatch(Batch::factory()->type('assign')->create())
            ->create();
        $deletedDownload = Download::factory()->forAssignment($deletedAssignment)->create();
        $deletedVideo->delete();

        $page = app(ListDownloads::class);
        $table = DownloadResource::table(Table::make($page));
        $column = $table->getColumn('assignment.videoWithTrashed.original_name');

        // preview_url has no backing database column since
        // database/migrations/2026_03_05_214958_remove_preview_from_video_table.php, so it is
        // set in memory here to verify the url() closure reads it from the non-deleted video.
        $freshDownload = $download->fresh();
        $freshDownload->assignment->videoWithTrashed->setAttribute(
            'preview_url',
            'https://example.com/preview.mp4'
        );
        $column->record($freshDownload);
        $this->assertSame('https://example.com/preview.mp4', $column->getUrl());

        $column->record($deletedDownload->fresh());
        $this->assertNull($column->getUrl());
    }
}
