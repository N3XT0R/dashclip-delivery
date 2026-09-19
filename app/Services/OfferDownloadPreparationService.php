<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Zip\AssignmentZipDto;
use App\Enum\DownloadStatusEnum;
use App\Jobs\BuildZipJob;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Repository\AssignmentRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class OfferDownloadPreparationService
{
    public function __construct(
        private readonly AssignmentRepository $assignments,
        private readonly DownloadCacheService $cache,
    ) {
    }

    /**
     * Prepare signed direct links as a fallback and enqueue an isolated ZIP build including info.csv.
     * The caller must validate the signature granting access to this channel and batch.
     *
     * @param list<int> $ids
     * @return array{jobId: string, status: string, downloads: list<array{name: string, url: string}>, progressUrl: string, downloadUrl: string}
     */
    public function prepare(Request $request, Channel $channel, array $ids, ?Batch $batch = null): array
    {
        $items = $this->assignments->fetchDownloadableForChannel($channel, collect($ids), $batch);
        abort_if($items->isEmpty(), 422, 'Die Auswahl ist nicht mehr verfügbar.');

        $expires = now()->addDay();
        $downloads = $items->map(fn (Assignment $assignment): array => [
            'name' => $assignment->video->original_name ?: basename($assignment->video->path),
            'url' => URL::temporarySignedRoute('offers.video.download', $expires, ['assignment' => $assignment->getKey()]),
        ])->values()->all();

        $jobId = (string) Str::uuid();
        $status = DownloadStatusEnum::QUEUED->value;

        try {
            $this->cache->init($jobId);
            BuildZipJob::dispatch(new AssignmentZipDto(
                batchId: $batch?->getKey(),
                channelId: $channel->getKey(),
                assignmentIds: $items->modelKeys(),
                ip: $request->ip(),
                userAgent: $request->userAgent(),
                jobId: $jobId,
            ), $request->user());
        } catch (Throwable $exception) {
            report($exception);
            $status = DownloadStatusEnum::FAILED->value;
            rescue(fn () => $this->cache->setStatus($jobId, $status));
        }

        return [
            'jobId' => $jobId,
            'status' => $status,
            'downloads' => $downloads,
            'progressUrl' => URL::temporarySignedRoute('zips.progress', $expires, ['id' => $jobId]),
            'downloadUrl' => URL::temporarySignedRoute('zips.download', $expires, ['id' => $jobId]),
        ];
    }
}
