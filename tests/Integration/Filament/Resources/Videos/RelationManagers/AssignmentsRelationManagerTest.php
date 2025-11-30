<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\Videos\RelationManagers;

use App\Filament\Resources\Videos\Pages\ViewVideo;
use App\Filament\Resources\Videos\RelationManagers\AssignmentsRelationManager;
use App\Models\Assignment;
use App\Models\User;
use App\Models\Video;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class AssignmentsRelationManagerTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function testAssignmentsRelationManagerShowsRecordsAndActions(): void
    {
        $video = Video::factory()->withPreviewUrl()->create();
        $assignment = Assignment::factory()->forVideo($video)->create();

        $this->actingAs($this->admin);

        Livewire::test(AssignmentsRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => ViewVideo::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$assignment])
            ->assertTableActionVisible('open')
            ->assertTableActionVisible('preview');
    }

    public function testPreviewActionHiddenWhenVideoMissingPreviewUrl(): void
    {
        $video = Video::factory()->create(['preview_url' => null]);
        $assignment = Assignment::factory()->forVideo($video)->create();

        $this->actingAs($this->admin);

        Livewire::test(AssignmentsRelationManager::class, [
            'ownerRecord' => $video,
            'pageClass' => ViewVideo::class,
        ])
            ->assertCanSeeTableRecords([$assignment])
            ->assertTableActionHidden('preview', $assignment);
    }
}
