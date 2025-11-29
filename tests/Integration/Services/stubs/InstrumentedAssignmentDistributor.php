<?php

declare(strict_types=1);

namespace Tests\Integration\Services\stubs;

use App\Models\Batch;
use App\DTO\ChannelPoolDto;
use App\Services\AssignmentDistributor;
use App\ValueObjects\AssignmentRun;
use Illuminate\Support\Collection;

readonly class InstrumentedAssignmentDistributor extends AssignmentDistributor
{
    /** @var Collection<int, array{quotaOverride:?int, batch:Batch, uploaderType:string, uploaderId:int|string}> */
    public Collection $prepareChannelCalls;

    /** @var Collection<int, AssignmentRun> */
    public Collection $assignGroupRuns;

    public function __construct(
        $assignmentRepository,
        $assignmentService,
        $channelVideoBlockRepository,
        $batchService
    ) {
        parent::__construct($assignmentRepository, $assignmentService, $channelVideoBlockRepository, $batchService);

        $this->prepareChannelCalls = collect();
        $this->assignGroupRuns = collect();
    }

    public function prepareChannelsOrAbort(
        ?int $quotaOverride,
        Batch $batch,
        string $uploaderType,
        string|int $uploaderId
    ): ChannelPoolDto {
        $this->prepareChannelCalls->push(compact('quotaOverride', 'batch', 'uploaderType', 'uploaderId'));

        return FakeChannelPoolDtoFactory::make();
    }

    public function assignGroups(AssignmentRun $run): array
    {
        $this->assignGroupRuns->push($run);

        return [0, 0];
    }

    public function buildGroups(Collection $poolVideos): Collection
    {
        return collect([$poolVideos]);
    }
}
