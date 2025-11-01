<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\Batches\RelationManagers;

use App\Enum\BatchTypeEnum;
use App\Filament\Resources\Batches\Pages\EditBatch;
use App\Filament\Resources\Batches\RelationManagers\ClipsRelationManager;
use App\Models\Batch;
use App\Models\Clip;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

/**
 * Integration test for the ClipsRelationManager.
 *
 * Verifies:
 *  - Relation manager table renders correctly for admin users
 *  - All expected columns are visible and properly configured
 *  - The relation manager binds correctly within the EditBatch context
 */
final class ClipsRelationManagerTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function testClipsRelationManagerRendersForAdmin(): void
    {
        $batch = Batch::factory()
            ->type(BatchTypeEnum::ASSIGN->value)
            ->create();

        $clip = Clip::factory()->create();

        $this->actingAs($this->admin);

        Livewire::test(ClipsRelationManager::class, [
            'ownerRecord' => $batch,
            'pageClass' => EditBatch::class, // <- Wichtig!
        ])
            ->assertSuccessful()
            ->assertTableColumnVisible('id')
            ->assertTableColumnVisible('video.original_name')
            ->assertTableColumnVisible('submitted_by')
            ->assertTableColumnVisible('start_time')
            ->assertTableColumnVisible('end_time')
            ->assertTableColumnVisible('created_at');
    }
}
