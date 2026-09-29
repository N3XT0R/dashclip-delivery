<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Enum\ProcessingStatusEnum;
use App\Enum\Video\DeliveredVersionEnum;
use App\Exceptions\Censor\VideoCensorException;
use App\Models\User;
use App\Models\Video;
use App\Repository\VideoRepository;
use App\Services\Censor\VideoCensorInterface;
use App\Services\Video\DownloadedChannelNotifier;
use App\Services\Video\PreviewRefresher;
use Illuminate\Support\Facades\Storage;

/**
 * Blurs the number plates of a video that carries only its untouched original, so a submitter can
 * ask for it after the upload as well.
 *
 * The blurred copy becomes the file that is handed out, the original stays beside it, and from
 * then on the submitter can switch between the two.
 */
readonly class BlurVideoNowUseCase
{
    public function __construct(
        private VideoCensorInterface $censor,
        private VideoRepository $videoRepository,
        private PreviewRefresher $previews,
        private DownloadedChannelNotifier $notifier,
    ) {
    }

    /**
     * @param Video $video
     * @param User|null $actor who asked for it, for the record
     * @return void
     * @throws VideoCensorException when the video cannot be blurred
     */
    public function handle(Video $video, ?User $actor = null): void
    {
        $this->guard($video);

        $disk = $video->getDisk();
        $originalPath = (string)$video->getAttribute('path');
        $blurredPath = $this->blurredPathFor($originalPath);

        $result = $this->censor->censor($disk->path($originalPath), $disk->path($blurredPath));

        $this->videoRepository->update($video, [
            'path' => $blurredPath,
            'source_path' => $originalPath,
            'delivered_version' => DeliveredVersionEnum::BLURRED->value,
            'censor_requested' => true,
            'bytes' => Storage::disk((string)$video->getAttribute('disk'))->size($blurredPath),
        ]);

        $video->refresh();
        $this->previews->refresh($video);
        $this->notifier->tellAbout($video, DeliveredVersionEnum::BLURRED);

        activity('videos')
            ->causedBy($actor)
            ->performedOn($video)
            ->withProperties([
                'video_id' => $video->getKey(),
                'regions_blurred' => $result->regionsBlurred,
            ])
            ->log('Number plates blurred on request');
    }

    /**
     * @throws VideoCensorException
     */
    private function guard(Video $video): void
    {
        if (!$this->censor->isAvailable()) {
            throw new VideoCensorException('Blurring is not set up on this installation.');
        }

        if ($video->hasBothVersions()) {
            throw new VideoCensorException('This video already carries a blurred copy.');
        }

        if ($video->getAttribute('processing_status') !== ProcessingStatusEnum::Completed) {
            throw new VideoCensorException('The video is not ready to be blurred.');
        }
    }

    private function blurredPathFor(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $withoutExtension = $extension === '' ? $path : substr($path, 0, -(strlen($extension) + 1));

        return $extension === ''
            ? $withoutExtension . '_blurred'
            : $withoutExtension . '_blurred.' . $extension;
    }
}
