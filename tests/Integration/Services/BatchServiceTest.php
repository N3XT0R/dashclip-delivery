<?php

declare(strict_types=1);

namespace Tests\Integration\Services;

use App\Enum\BatchTypeEnum;
use App\Models\Batch;
use App\Services\BatchService;
use App\ValueObjects\IngestStats;
use RuntimeException;
use Tests\DatabaseTestCase;

class BatchServiceTest extends DatabaseTestCase
{
    protected BatchService $batchService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batchService = $this->app->make(BatchService::class);
    }


    public function testReturnsLatestFinishedAssignBatch(): void
    {
        // Arrange: some noise batches that must be ignored
        Batch::factory()->type('notify')->finished(['emails' => 1])->create(); // other type, finished
        Batch::factory()->type(BatchTypeEnum::ASSIGN->value)->create();             // assign, NOT finished

        // Arrange: two finished assign batches; should return the one with the highest id
        $older = Batch::factory()
            ->type(BatchTypeEnum::ASSIGN->value)
            ->finished(['expired' => 3])
            ->create();

        $newer = Batch::factory()
            ->type(BatchTypeEnum::ASSIGN->value)
            ->finished(['expired' => 7])
            ->create();

        // Act
        $result = $this->batchService->getLatestAssignBatch();

        // Assert: latest by id and finished
        $this->assertTrue($result->is($newer));
        $this->assertNotNull($result->finished_at);
        $this->assertSame(BatchTypeEnum::ASSIGN->value, $result->type);
    }

    public function testThrowsWhenNoFinishedAssignBatchFound(): void
    {
        // Arrange: only non-finished or other types present
        Batch::factory()->type(BatchTypeEnum::ASSIGN->value)->create();           // not finished
        Batch::factory()->type('notify')->finished(['emails' => 2])->create(); // finished but wrong type

        // Assert: service throws RuntimeException with expected message
        $this->withoutExceptionHandling();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Kein Assign-Batch gefunden.');

        // Act
        $this->batchService->getLatestAssignBatch();
    }

    public function testItUpdatesTheBatchWithTheGivenIngestStats(): void
    {
        $batch = Batch::factory()->type(BatchTypeEnum::INGEST->value)->create();
        $stats = IngestStats::fromArray(['new' => 5, 'dups' => 2, 'err' => 1]);

        $result = $this->batchService->updateStats($batch, $stats);
        self::assertTrue($result);

        $this->assertDatabaseHas('batches', [
            'id' => $batch->getKey(),
            'stats->new' => $stats->getNew(),
            'stats->dups' => $stats->getDups(),
            'stats->err' => $stats->getErr(),
        ]);
    }

    public function testItFinalizesTheBatchWithTheGivenIngestStats(): void
    {
        $batch = Batch::factory()->type(BatchTypeEnum::INGEST->value)->create();
        $stats = IngestStats::fromArray(['new' => 5, 'dups' => 2, 'err' => 1]);

        $result = $this->batchService->finalizeStats($batch, $stats);
        self::assertTrue($result);

        $this->assertDatabaseHas('batches', [
            'id' => $batch->getKey(),
            'stats->new' => $stats->getNew(),
            'stats->dups' => $stats->getDups(),
            'stats->err' => $stats->getErr(),
        ]);

        $batch->refresh();
        $this->assertNotNull($batch->finished_at);
    }

}
