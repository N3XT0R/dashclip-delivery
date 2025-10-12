<?php

namespace App\Jobs;

use App\Facades\Cfg;
use App\Models\User;
use App\Models\Video;
use App\Services\IngestScanner;
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
        public string $path,
        public string $originalName,
        public string $ext,
        public int $start,
        public int $end,
        public ?string $submittedBy,
        public ?string $note = null,
        public ?string $bundleKey = null,
        public ?string $role = null,
    ) {
        $this->hash = hash_file('sha256', $path);
    }

    public function handle(IngestScanner $scanner): void
    {
        $disk = Cfg::get('default_file_system', 'default', 'dropbox');
        $scanner->processFile($this->path, $this->ext, $this->originalName, $disk);

        $video = Video::query()->where('hash', $this->hash)->first();

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
