<?php

namespace App\Jobs;

use App\DTO\FileInfoDto;
use App\Facades\DynamicStorage;
use App\Models\Clip;
use App\Models\Team;
use App\Models\User;
use App\Models\Video;
use App\Services\Ingest\IngestScanner;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessUploadedVideo implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected string $hash;

    public $tries = 0;

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
        public ?Team $team = null,
    ) {
        $disk = \Storage::disk($sourceDisk);
        $this->hash = DynamicStorage::getHashForFileInfoDto($disk, $fileInfoDto);
    }


    public function uniqueId(): string
    {
        return "{$this->user->getKey()}:{$this->hash}";
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
        $user = $this->user;
        $sourceDiskName = $this->sourceDisk;
        $disk = \Storage::disk($sourceDiskName);
        $scanner->processFile(
            inboxDisk: $disk,
            file: $fileInfoDto,
            diskName: $this->targetDisk,
            user: $user,
            inboxDiskName: $sourceDiskName,
        );

        $video = Video::query()
            ->where('original_name', $fileInfoDto->originalName ?? $fileInfoDto->basename)
            ->where('hash', $this->hash)
            ->orderByDesc('created_at')
            ->first();

        if ($video) {
            activity()
                ->performedOn($video)
                ->causedBy($this->user)
                ->withProperties([
                    'action' => 'upload',
                    'file' => $video->original_name,
                    'attempt' => $this->attempts(),
                ])
                ->log('uploaded a video');

            /**
             * @var Clip $clip
             */
            $clip = $video->clips()->create([
                'start_sec' => $this->start,
                'end_sec' => $this->end,
                'submitted_by' => $this->submittedBy,
                'note' => $this->note,
                'bundle_key' => $this->bundleKey,
                'role' => $this->role,
            ]);

            $clip?->setUser($this->user)
                ->save();

            if ($this->team) {
                $video->setAttribute('team_id', $this->team->getKey());
                $video->save();
            }
        }
    }
}
