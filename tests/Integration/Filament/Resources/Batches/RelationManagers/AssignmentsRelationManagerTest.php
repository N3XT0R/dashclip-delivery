<?php

declare(strict_types=1);

namespace Tests\Integration\Filament\Resources\Batches\RelationManagers;

use App\Enum\BatchTypeEnum;
use App\Filament\Resources\Assignments\AssignmentResource;
use App\Filament\Resources\Batches\RelationManagers\AssignmentsRelationManager;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Models\User;
use App\Services\LinkService;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Mockery;
use Tests\DatabaseTestCase;

/**
 * Integration test for the AssignmentsRelationManager.
 *
 * Verifies:
 *  - Relation manager table renders correctly for admin users
 *  - "Open" and "Open Offer" record actions are visible
 *  - Offer link generation uses LinkService correctly
 */
final class AssignmentsRelationManagerTest extends DatabaseTestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
    }

    public function testAssignmentsRelationManagerRendersForAdmin(): void
    {
        $batch = Batch::factory()->create();
        $assignment = Assignment::factory()->for($batch)->create();

        $this->actingAs($this->admin);

        Livewire::test(AssignmentsRelationManager::class, [
            'ownerRecord' => $batch,
            'pageClass' => AssignmentResource::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$assignment])
            ->assertTableActionVisible('open')
            ->assertTableActionVisible('offer_link');
    }

    public function testOfferLinkActionUsesLinkService(): void
    {
        $batch = Batch::factory()
            ->type(BatchTypeEnum::ASSIGN->value)
            ->create();

        $channel = Channel::factory()->create();

        $assignment = Assignment::factory()
            ->for($batch)
            ->for($channel)
            ->create([
                'expires_at' => Carbon::parse('2030-01-01'),
            ]);

        $mockedService = Mockery::mock(LinkService::class);
        $mockedService->shouldReceive('getOfferUrl')
            ->atLeast()->once()
            ->withArgs(function ($argBatch, $argChannel, $argExpireAt) use ($batch, $channel) {
                return $argBatch->is($batch)
                    && $argChannel->is($channel)
                    && $argExpireAt instanceof Carbon;
            })
            ->andReturn('https://example.com/offer');

        $this->app->instance(LinkService::class, $mockedService);

        $this->actingAs($this->admin);

        Livewire::test(AssignmentsRelationManager::class, [
            'ownerRecord' => $batch,
            'pageClass' => AssignmentResource::class,
        ])
            ->callTableAction('offer_link', $assignment)
            ->assertHasNoTableActionErrors();

        Mockery::close();
    }


}
