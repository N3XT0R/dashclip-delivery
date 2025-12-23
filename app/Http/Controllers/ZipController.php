<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\DownloadStatusEnum;
use App\Jobs\BuildZipJob;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Repository\AssignmentRepository;
use App\Services\AssignmentService;
use App\Services\DownloadCacheService;
use Filament\Facades\Filament;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZipController extends Controller
{
    public function __construct(
        private AssignmentService $assignments,
        private DownloadCacheService $cache,
        private AssignmentRepository $assignmentRepository,
    ) {
    }

    /**
     * Starts a zip creation job for the given batch and channel.
     * @param Request $req
     * @param Batch $batch
     * @param Channel $channel
     * @return JsonResponse
     * @deprecated use startForChannel instead
     */
    // POST /zips/{batch}/{channel} -> Starts Job
    public function start(Request $req, Batch $batch, Channel $channel): JsonResponse
    {
        $validated = $req->validate([
            'assignment_ids' => ['required', 'array', 'min:1'],
        ]);

        $batchId = $batch->getKey();
        $jobId = $batchId . '_' . $channel->getKey();

        $ids = collect($validated['assignment_ids'])
            ->filter(static fn($v) => ctype_digit((string)$v))
            ->map(static fn($v) => (int)$v)
            ->values();


        $items = $this->assignments->fetchForZip($batch, $channel, $ids);

        if ($items->isEmpty()) {
            return response()->json(['error' => 'Die Auswahl ist nicht mehr verfügbar.'], 422);
        }

        // initialer Status
        $this->cache->init($jobId);

        BuildZipJob::dispatch($batchId, $channel->getKey(), $ids->all(), $req->ip(), $req->userAgent());

        return response()->json(['jobId' => $jobId, 'status' => DownloadStatusEnum::QUEUED->value]);
    }

    /**
     * Start a zip creation job for the given channel without a batch.
     * @param Request $req
     * @param Channel $channel
     * @return JsonResponse
     */
    public function startForChannel(Request $req, Channel $channel): JsonResponse
    {
        $validated = $req->validate([
            'assignment_ids' => ['required', 'array', 'min:1'],
        ]);

        $jobId = $channel->getKey() . '_' . Str::uuid();

        $ids = collect($validated['assignment_ids'])
            ->filter(static fn($v) => ctype_digit((string)$v))
            ->map(static fn($v) => (int)$v)
            ->values();


        $items = $this->assignmentRepository->fetchForZipWithoutBatch($channel, $ids);

        if ($items->isEmpty()) {
            return response()->json(['error' => 'Die Auswahl ist nicht mehr verfügbar.'], 422);
        }

        // initialer Status
        $this->cache->init($jobId);

        BuildZipJob::dispatch(
            channelId: $channel->getKey(),
            assignmentIds: $ids->all(),
            ip: $req->ip(),
            userAgent: $req->userAgent(),
            batchId: null,
        );

        return response()->json(['jobId' => $jobId, 'status' => DownloadStatusEnum::QUEUED->value]);
    }

    // GET /zips/{id}/progress ->  Polling for Frontend
    public function progress(string $id)
    {
        $status = $this->cache->getStatus($id);
        $progress = $this->cache->getProgress($id);
        $name = $status === DownloadStatusEnum::READY->value ? $this->cache->getName($id) : null;

        return response()->json(compact('status', 'progress', 'name'));
    }

    // GET /zips/{id}/download -> delivers zip
    public function download(Request $req, string $id)
    {
        $path = $this->cache->getFile($id);
        $name = $this->cache->getName($id, "{$id}.zip");

        if (!$path) {
            abort(404);
        }

        $fullPath = Storage::exists($path) ? Storage::path($path) : $path;
        if (!is_file($fullPath)) {
            abort(404);
        }

        if (false === (bool)Filament::auth()?->check()) {
            $assignmentIds = $this->cache->getAssignments($id);
            if ($assignmentIds !== []) {
                Assignment::query()->whereIn('id', $assignmentIds)->get()->each(
                    fn(Assignment $assignment) => $this->assignments->markDownloaded(
                        $assignment,
                        $req->ip(),
                        $req->userAgent(),
                    )
                );
            }
        }

        return response()->download($fullPath, $name)->deleteFileAfterSend();
    }
}
