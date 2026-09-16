<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\StatusEnum;
use App\Exceptions\IO\FileReadException;
use App\Models\Assignment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OfferDownloadService
{
    public function __construct(private readonly AssignmentService $assignments)
    {
    }

    /**
     * Stream an authorized offer and track it after the server finishes sending the file.
     * The caller must authorize channel access. Unavailable offers return 410 and missing files 404.
     */
    public function download(Assignment $assignment, string $ip, ?string $userAgent): StreamedResponse
    {
        abort_unless(in_array($assignment->status, StatusEnum::getReturnableStatuses(), true), 410);
        abort_if($assignment->expires_at !== null && $assignment->expires_at->isPast(), 410);

        $video = $assignment->video;
        abort_if($video === null || !$video->path, 404);
        $disk = Storage::disk($video->disk ?? 'local');
        abort_unless($disk->exists($video->path), 404);
        $stream = $disk->readStream($video->path);
        abort_unless(is_resource($stream), 404);
        $size = $disk->size($video->path);

        return response()->streamDownload(function () use ($stream, $size, $assignment, $ip, $userAgent): void {
            try {
                $bytes = fpassthru($stream);
                if ($bytes !== $size) {
                    throw new FileReadException('The offered video transfer was incomplete.');
                }
                if (!connection_aborted()) {
                    DB::transaction(fn () => $this->assignments->markDownloaded($assignment, $ip, $userAgent));
                }
            } finally {
                fclose($stream);
            }
        }, basename($video->path), ['Content-Type' => 'application/octet-stream', 'Content-Length' => (string) $size, 'Cache-Control' => 'private, no-store']);
    }
}
