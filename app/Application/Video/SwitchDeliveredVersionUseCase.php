<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Enum\ProcessingStatusEnum;
use App\Enum\Video\DeliveredVersionEnum;
use App\Exceptions\Video\VersionNotSwitchableException;
use App\Models\User;
use App\Models\Video;
use App\Notifications\VideoVersionSwitchedNotification;
use App\Repository\ClipRepository;
use App\Repository\VideoRepository;
use App\Services\PreviewService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Hands out the other file of a video from now on: the untouched original instead of the blurred
 * copy, or the other way round.
 *
 * The previews are made again, because they are cut from the file that gets handed out and would
 * otherwise still show the plates of the version nobody is supposed to see. Channels that already
 * downloaded the video are told, since what they hold is no longer what the submitter wants out.
 */
readonly class SwitchDeliveredVersionUseCase
{
    public function __construct(
        private VideoRepository $videoRepository,
        private ClipRepository $clipRepository,
        private PreviewService $previewService,
    ) {
    }

    /**
     * @param Video $video
     * @param User|null $actor who asked for the switch, for the record
     * @return DeliveredVersionEnum the version that is handed out from now on
     * @throws VersionNotSwitchableException when the video has nothing to switch to
     */
    public function handle(Video $video, ?User $actor = null): DeliveredVersionEnum
    {
        $this->guard($video);

        $delivered = $video->getAttribute('delivered_version');
        $switched = $delivered instanceof DeliveredVersionEnum
            ? $delivered->other()
            : DeliveredVersionEnum::ORIGINAL;

        DB::transaction(function () use ($video, $switched): void {
            $this->videoRepository->update($video, [
                'path' => $video->getAttribute('source_path'),
                'source_path' => $video->getAttribute('path'),
                'delivered_version' => $switched->value,
                'version_switched_at' => now(),
            ]);
        });

        $video->refresh();
        $this->renewPreviews($video);
        $this->tellChannelsThatHaveIt($video, $switched);

        activity('videos')
            ->causedBy($actor)
            ->performedOn($video)
            ->withProperties([
                'video_id' => $video->getKey(),
                'delivered_version' => $switched->value,
            ])
            ->log('Delivered video version switched');

        return $switched;
    }

    /**
     * @throws VersionNotSwitchableException
     */
    private function guard(Video $video): void
    {
        if (!$video->hasBothVersions()) {
            throw new VersionNotSwitchableException('This video has only one version.');
        }

        if ($video->getAttribute('processing_status') === ProcessingStatusEnum::Deleted) {
            throw new VersionNotSwitchableException('The files of this video were already removed.');
        }
    }

    /**
     * Cut the previews from the file that is handed out now.
     */
    private function renewPreviews(Video $video): void
    {
        $diskName = (string)config('preview.default_disk', 'public');
        $previewDisk = Storage::disk($diskName);

        foreach ($video->clipsWithTrashed()->get() as $clip) {
            $path = $this->previewService->generatePreviewForClip($clip, $previewDisk, force: true);

            $this->clipRepository->update($clip, [
                'preview_path' => $path,
                'preview_disk' => $diskName,
            ]);
        }
    }

    private function tellChannelsThatHaveIt(Video $video, DeliveredVersionEnum $switched): void
    {
        foreach ($this->videoRepository->channelsThatDownloaded($video) as $channel) {
            foreach ($channel->channelUsers as $user) {
                $user->notify(new VideoVersionSwitchedNotification($video, $channel, $switched));
            }
        }
    }
}
