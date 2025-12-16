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
use Illuminate\Contracts\Filesystem\Filesystem;
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
        public string|Filesystem $sourceDisk,
        public int $start,
        public int $end,
        public ?string $submittedBy,
        public ?string $note = null,
        public ?string $bundleKey = null,
        public ?string $role = null,
        public ?Team $team = null,
    ) {
        $disk = $this->retrieveSourceDisk($sourceDisk);
        $this->hash = DynamicStorage::getHashForFileInfoDto($disk, $fileInfoDto);
    }

    private function retrieveSourceDisk(string|Filesystem $sourceDisk): Filesystem
    {
        if ($sourceDisk instanceof Filesystem) {
            return $sourceDisk;
        }
        return \Storage::disk($sourceDisk);
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
        $disk = $this->retrieveSourceDisk($this->sourceDisk);
        $scanner->processFile($disk, $fileInfoDto, $this->targetDisk, $user);

        $video = Video::query()
            ->orWhere('original_name', $fileInfoDto->originalName ?? $fileInfoDto->basename)
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
