<?php

declare(strict_types=1);

namespace App\Application\Api;

use App\Enum\ProcessingStatusEnum;
use App\Events\Video\VideoQueuedForIngest;
use App\Models\User;
use App\Models\Video;
use App\Repository\ClipRepository;
use App\Repository\TeamRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UploadVideoUseCase
{
    public function __construct(
        private readonly TeamRepository $teamRepository,
        private readonly ClipRepository $clipRepository,
    ) {
    }

    /**
     * Store the uploaded file on the videos disk, create the video record with
     * its initial clip and queue it for the ingest pipeline.
     */
    public function execute(UploadedFile $file, int $startSec, int $endSec, User $user): Video
    {
        $path = $file->store('', 'videos');

        $video = Video::query()->create([
            'original_name' => $file->getClientOriginalName(),
            'ext' => strtoupper($file->getClientOriginalExtension()),
            'bytes' => Storage::disk('videos')->size($path),
            'path' => $path,
            'disk' => 'videos',
            'processing_status' => ProcessingStatusEnum::Pending,
            'team_id' => $this->teamRepository->getDefaultTeamForUser($user)?->getKey(),
        ]);

        $this->clipRepository->create([
            'video_id' => $video->getKey(),
            'user_id' => $user->getKey(),
            'submitted_by' => $user->display_name,
            'start_sec' => $startSec,
            'end_sec' => $endSec,
        ]);

        VideoQueuedForIngest::dispatch($video, $user);

        return $video;
    }
}
