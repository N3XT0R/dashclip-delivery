<?php

declare(strict_types=1);

namespace App\Jobs;

use AnourValar\EloquentSerialize\Facades\EloquentSerializeFacade;
use App\Exceptions\Zip\ZipEmptyException;
use App\Repository\AssignmentRepository;
use App\Repository\ChannelRepository;
use App\Services\OfferExportFileService;
use App\Services\Zip\ZipService;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Builds the ZIP of an offer export in place of Filament's CSV preparation job.
 */
class BuildOfferExportZipJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public bool $deleteWhenMissingModels = true;

    public ?int $tries = 1;

    public int $timeout = 3600;

    /**
     * Same signature as Filament's preparation job, which the export action instantiates.
     * @param array<string, string> $columnMap
     * @param array<string, mixed> $options
     * @param array<Model>|null $records
     */
    public function __construct(
        protected Export $export,
        protected string $query,
        protected array $columnMap,
        protected array $options = [],
        protected int $chunkSize = 100,
        protected ?array $records = null,
    ) {
    }

    public function handle(
        ZipService $zips,
        AssignmentRepository $assignments,
        ChannelRepository $channels,
        OfferExportFileService $files,
    ): void {
        $requested = $this->requestedIds();
        $channel = $channels->findById((int) ($this->options['channel_id'] ?? 0));
        $items = $channel !== null
            ? $assignments->fetchDownloadableForChannel($channel, $requested)
            : collect();
        $packed = collect();

        foreach ($requested->diff($items->modelKeys()) as $unavailableId) {
            Log::warning('Offered video skipped in ZIP download', [
                'reason' => 'offer_unavailable',
                'job_id' => $this->jobId(),
                'assignment_id' => $unavailableId,
                'channel_id' => $channel?->getKey(),
            ]);
        }

        try {
            if ($channel !== null && $items->isNotEmpty()) {
                $packed = $zips->buildArchive($files->absoluteZipPath($this->export), $channel, $items, $this->jobId());
                $files->writePackedIds($this->export, $packed->modelKeys());
            }
        } catch (ZipEmptyException) {
            // every skipped offer was already logged one by one
        } finally {
            $this->export->forceFill([
                'total_rows' => $requested->count(),
                'processed_rows' => $requested->count(),
                'successful_rows' => $packed->count(),
            ])->save();
        }
    }

    /** Log every selected offer that could not be delivered because the build failed. */
    public function failed(?Throwable $exception): void
    {
        foreach ($this->requestedIds() as $assignmentId) {
            Log::warning('Offered video not delivered because the ZIP download failed', [
                'reason' => 'zip_failed',
                'message' => $exception?->getMessage(),
                'job_id' => $this->jobId(),
                'assignment_id' => $assignmentId,
                'channel_id' => $this->options['channel_id'] ?? null,
            ]);
        }
    }

    /** @return Collection<int, int> */
    private function requestedIds(): Collection
    {
        if ($this->records !== null) {
            return collect($this->records)->map(fn (Model $record): int => (int) $record->getKey())->values();
        }

        $query = EloquentSerializeFacade::unserialize($this->query);

        return $query->pluck($query->getModel()->getQualifiedKeyName())->map(fn ($id): int => (int) $id)->values();
    }

    private function jobId(): string
    {
        return 'export-'.$this->export->getKey();
    }
}
