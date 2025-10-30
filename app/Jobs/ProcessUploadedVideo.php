<?php

namespace App\Jobs;

use App\DTO\FileInfoDto;
use App\Models\User;
use App\Models\Video;
use App\Services\Ingest\IngestScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUploadedVideo implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $hash;

    public function __construct(
        public User $user,
        public FileInfoDto $fileInfoDto,
        public string $targetDisk,
        public string $sourceDisk,
        public int $start,
        public int $end,
        public ?string $submittedBy,
        public ?string $note = null,
        public ?string $bundleKey = null,
        public ?string $role = null,
    ) {
    }

    /**
     * Handle the job for processing the uploaded video.
     * @param  IngestScanner  $scanner
     * @return void
     * @throws \Throwable
     */
    public function handle(IngestScanner $scanner): void
    {
        $fileInfoDto = $this->fileInfoDto;
        $disk = \Storage::disk($this->sourceDisk);
        $scanner->processFile($disk, $fileInfoDto, $this->targetDisk);

        $video = Video::query()
            ->where('original_name', $fileInfoDto->originalName)
            ->orWhere('original_name', $fileInfoDto->basename)
            ->orderByDesc('created_at')
            ->first();

        if ($video) {
            activity()
                ->performedOn($video)
                ->causedBy($this->user)
                ->withProperties(['action' => 'upload', ['file' => $video->original_name]])
                ->log('uploaded a video');
            $video->clips()->create([
                'start_sec' => $this->start,
                'end_sec' => $this->end,
                'submitted_by' => $this->submittedBy,
                'note' => $this->note,
                'bundle_key' => $this->bundleKey,
                'role' => $this->role,
            ]);
        }
    }
}
