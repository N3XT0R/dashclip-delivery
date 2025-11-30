<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\Videos\RelationManagers;

use App\Filament\Resources\Videos\Pages\ViewVideo;
use App\Filament\Resources\Videos\RelationManagers\ClipsRelationManager;
use App\Models\Clip;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class ClipsRelationManagerTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function testClipsRelationManagerShowsColumnsAndActions(): void
    {
        $video = Video::factory()->withPreviewUrl()->create();
        $clip = Clip::factory()->forVideo($video)->create();

        $this->actingAs($this->admin);

        Livewire::test(ClipsRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => ViewVideo::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$clip])
            ->assertTableColumnVisible('id')
            ->assertTableColumnVisible('video.original_name')
            ->assertTableColumnVisible('start_sec')
            ->assertTableColumnVisible('end_sec')
            ->assertTableColumnVisible('submitted_by')
            ->assertTableColumnVisible('created_at')
            ->assertTableActionVisible('view', $clip)
            ->assertTableActionVisible('preview', $clip);
    }

    public function testPreviewActionHiddenWhenVideoHasNoPreviewUrl(): void
    {
        $video = Video::factory()->create(['preview_url' => null]);
        $clip = Clip::factory()->forVideo($video)->create();

        $this->actingAs($this->admin);

        Livewire::test(ClipsRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => ViewVideo::class,
        ])
            ->assertCanSeeTableRecords([$clip])
            ->assertTableActionHidden('preview', $clip);
    }
}
