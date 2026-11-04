<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\DownloadStatusEnum;
use App\Models\Assignment;
use App\Models\Batch;
use App\Models\Channel;
use App\Services\AssignmentService;
use App\Services\DownloadCacheService;
use App\Services\OfferDownloadPreparationService;
use App\Services\OfferDownloadService;
use App\Repository\AssignmentRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ZipController extends Controller
{
    public function __construct(
        private readonly AssignmentService $assignments,
        private readonly DownloadCacheService $cache,
        private readonly AssignmentRepository $assignmentRepository,
        private readonly OfferDownloadPreparationService $preparation,
    ) {
    }

    /** Prepare downloads authorized by the signed batch and channel URL. */
    public function start(Request $request, Batch $batch, Channel $channel): JsonResponse
    {
        return response()->json($this->preparation->prepare($request, $channel, $this->selectedIds($request), $batch));
    }

    /** Return a snapshot that remains available even if a client misses updates. */
    public function progress(string $id): JsonResponse
    {
        return response()->json([
            'status' => $this->cache->getStatus($id),
            'progress' => $this->cache->getProgress($id),
            'name' => $this->cache->getName($id),
            'files' => $this->cache->getFiles($id),
        ])->header('Cache-Control', 'private, no-store');
    }

    /** Serve the archive without deleting it, so the browser can retry or resume. */
    public function download(Request $request, string $id): BinaryFileResponse
    {
        $path = $this->cache->getFile($id);
        abort_unless($path && $this->cache->getStatus($id) === DownloadStatusEnum::READY->value, 404);
        $fullPath = Storage::exists($path) ? Storage::path($path) : $path;
        abort_unless(is_file($fullPath), 404);

        foreach ($this->assignmentRepository->findByIds($this->cache->getAssignments($id)) as $assignment) {
            $this->assignments->markDownloaded($assignment, $request->ip(), $request->userAgent());
        }

        return response()->download($fullPath, $this->cache->getName($id, "{$id}.zip"), ['Cache-Control' => 'private, no-store']);
    }

    /** Stream an individually authorized video without a ZIP job or WebSocket connection. */
    public function video(Request $request, Assignment $assignment, OfferDownloadService $downloads): StreamedResponse
    {
        return $downloads->download($assignment, $request->ip(), $request->userAgent());
    }

    /** @return list<int> */
    private function selectedIds(Request $request): array
    {
        $validated = $request->validate([
            'assignment_ids' => ['required', 'array', 'min:1', 'max:500'],
            'assignment_ids.*' => ['required', 'integer', 'min:1'],
        ]);

        return array_values(array_unique(array_map(intval(...), $validated['assignment_ids'])));
    }
}
