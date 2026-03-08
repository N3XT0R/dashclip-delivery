<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources;

use App\Filament\Admin\Resources\Batches\Pages\ListBatches;
use App\Filament\Resources\Batches\RelationManagers\ChannelsRelationManager;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\User;
use Livewire\Livewire;
use Tests\DatabaseTestCase;

final class BatchResourceTest extends DatabaseTestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->admin()->create();
        $this->actingAs($this->user);
    }

    public function testListBatchesShowsTabsAndAssignmentCount(): void
    {
        $ingest = Batch::factory()->type('ingest')->create();
        $notify = Batch::factory()->type('notify')->create();
        $assign = Batch::factory()->type('assign')->create();

        Assignment::factory()->count(5)->withBatch($assign)->create();

        Livewire::test(ListBatches::class, ['activeTab' => 'assign'])
            ->loadTable()
            ->assertCanSeeTableRecords([$assign])
            ->assertCanNotSeeTableRecords([$ingest, $notify])
            ->assertTableColumnExists('assignments_count');

        Livewire::test(ListBatches::class, ['activeTab' => 'ingest'])
            ->assertCanSeeTableRecords([$ingest])
            ->assertCanNotSeeTableRecords([$assign, $notify]);

        Livewire::test(ListBatches::class, ['activeTab' => 'notify'])
            ->assertCanSeeTableRecords([$notify])
            ->assertCanNotSeeTableRecords([$assign, $ingest]);
    }
}
