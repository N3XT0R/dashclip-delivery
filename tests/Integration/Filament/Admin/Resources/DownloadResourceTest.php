<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Admin\Resources;

use App\Filament\Admin\Resources\Downloads\DownloadResource;
use App\Filament\Admin\Resources\Downloads\Pages\ListDownloads;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Enum\ProcessingStatusEnum;
use App\Models\Clip;
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

    public function testVideoColumnLinksThePreviewUntilTheFilesAreGone(): void
    {
        $storedDownload = $this->downloadOfVideoWithPreview('previews/stored.mp4');
        $markedDownload = $this->downloadOfVideoWithPreview('previews/marked.mp4', deleted: true);
        $purgedDownload = $this->downloadOfVideoWithPreview('previews/purged.mp4', deleted: true, filesRemoved: true);

        $page = app(ListDownloads::class);
        $table = DownloadResource::table(Table::make($page));
        $column = $table->getColumn('assignment.videoWithTrashed.original_name');

        $column->record($storedDownload->fresh());
        self::assertStringContainsString('previews/stored.mp4', (string)$column->getUrl());

        // a video that is only marked as deleted keeps its preview until the files are removed
        $column->record($markedDownload->fresh());
        self::assertStringContainsString('previews/marked.mp4', (string)$column->getUrl());

        $column->record($purgedDownload->fresh());
        self::assertNull($column->getUrl());
    }

    private function downloadOfVideoWithPreview(
        string $previewPath,
        bool $deleted = false,
        bool $filesRemoved = false
    ): Download {
        $video = Video::factory()->create([
            'processing_status' => $filesRemoved
                ? ProcessingStatusEnum::Deleted
                : ProcessingStatusEnum::Completed,
        ]);
        Clip::factory()->forVideo($video)->create(['preview_path' => $previewPath]);
        $assignment = Assignment::factory()->forVideo($video)
            ->withBatch(Batch::factory()->type('assign')->create())
            ->create();
        $download = Download::factory()->forAssignment($assignment)->create();

        if ($deleted) {
            $video->delete();
        }

        return $download;
    }
}
