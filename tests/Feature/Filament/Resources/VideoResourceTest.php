<?php

declare(strict_types=1);

namespace Tests\Feature\Filament\Resources;

use App\Enum\Guard\GuardEnum;
use App\Filament\Resources\Videos\Pages\ListVideos;
use App\Models\User;
use App\Models\Video;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration tests for the Filament VideoResource.
 *
 * Verifies:
 *  - ListVideos page renders table correctly with expected columns
 *  - Filters (disk/ext/date range) are available
 *  - Preview and View actions work and delete action visibility depends on role
 *  - Default sorting and data rendering
 */
final class VideoResourceTest extends DatabaseTestCase
{
    private User $admin;
    private User $regular;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->regular = User::factory()->standard(GuardEnum::DEFAULT)->create();
    }

    public function testListVideosRendersTableWithExpectedColumns(): void
    {
        $videos = Video::factory()->count(2)->create([
            'original_name' => 'test_video.mp4',
            'ext' => 'mp4',
            'bytes' => 2048,
            'disk' => 'local',
            'created_at' => Carbon::parse('2024-10-15 10:00:00'),
        ]);

        foreach ($videos as $video) {
            $video->clips()->create([
                'start_sec' => 0,
                'end_sec' => 5,
                'preview_disk' => 'preview',
                'preview_path' => 'previews/v1.mp4',
            ]);
        }

        $this->actingAs($this->admin);

        Livewire::test(ListVideos::class)
            ->assertStatus(200)
            ->assertCanSeeTableRecords($videos)
            ->assertTableColumnExists('original_name')
            ->assertTableColumnExists('ext')
            ->assertTableColumnExists('bytes')
            ->assertTableColumnExists('disk')
            ->assertTableColumnExists('assignments_count')
            ->assertTableColumnExists('clips.user.display_name')
            ->assertTableColumnExists('clips.submitted_by')
            ->assertTableColumnExists('created_at')
            ->assertSeeText('test_video.mp4');
    }

    public function testFiltersAreVisibleAndWork(): void
    {
        $videoA = Video::factory()->create(['ext' => 'mp4', 'disk' => 'local']);
        $videoB = Video::factory()->create(['ext' => 'avi', 'disk' => 's3']);

        $videoA->clips()->create([
            'start_sec' => 0,
            'end_sec' => 5,
            'preview_disk' => 'preview',
            'preview_path' => 'previews/v1.mp4',
        ]);

        $videoB->clips()->create([
            'start_sec' => 0,
            'end_sec' => 5,
            'preview_disk' => 'preview',
            'preview_path' => 'previews/v1.mp4',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListVideos::class)
            ->assertStatus(200)
            ->set('tableFilters.ext.value', 'mp4')
            ->assertCanSeeTableRecords([$videoA])
            ->assertCanNotSeeTableRecords([$videoB]);
    }

    public function testAdminSeesDeleteActionWhileRegularUserDoesNot(): void
    {
        $video = Video::factory()->create();
        $video->clips()->create([
            'start_sec' => 0,
            'end_sec' => 5,
            'preview_disk' => 'preview',
            'preview_path' => 'previews/v1.mp4',
        ]);

        // Admin: darf löschen
        $this->actingAs($this->admin);
        Livewire::test(ListVideos::class)
            ->assertTableActionVisible('delete', $video);

        // Regular User: darf nicht löschen
        $this->actingAs($this->regular);
        Livewire::test(ListVideos::class)
            ->assertTableActionHidden('delete', $video);
    }

    public function testPreviewAndViewActionsAreAvailable(): void
    {
        $video = Video::factory()->create();
        $video->clips()->create([
            'start_sec' => 0,
            'end_sec' => 5,
            'preview_disk' => 'preview',
            'preview_path' => 'previews/v1.mp4',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListVideos::class)
            ->assertTableActionVisible('preview', $video)
            ->assertTableActionVisible('view', $video);
    }

    public function testDefaultSortingShowsNewestFirst(): void
    {
        $older = Video::factory()->create(['created_at' => now()->subDay()]);
        $older->clips()->create([
            'start_sec' => 0,
            'end_sec' => 5,
            'preview_disk' => 'preview',
            'preview_path' => 'previews/v1.mp4',
        ]);
        $newer = Video::factory()->create(['created_at' => now()]);
        $newer->clips()->create([
            'start_sec' => 0,
            'end_sec' => 5,
            'preview_disk' => 'preview',
            'preview_path' => 'previews/v1.mp4',
        ]);

        $this->actingAs($this->admin);

        Livewire::test(ListVideos::class)
            ->assertCanSeeTableRecords([$newer, $older])
            ->tap(function ($livewire) use ($newer) {
                $first = $livewire->instance()->getTableRecords()->first();
                $this->assertTrue($first->is($newer));
            });
    }
}
