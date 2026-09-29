<?php

declare(strict_types=1);

namespace App\Pipelines\Ingest\Step;

use App\Enum\Ingest\IngestStepEnum;
use App\Enum\Team\TeamSettingEnum;
use App\Exceptions\Censor\VideoCensorException;
use App\Models\Video;
use App\Pipelines\Ingest\Context\IngestContext;
use App\Repository\TeamSettingRepository;
use App\Repository\VideoRepository;
use App\Services\Censor\VideoCensorInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Blurs the number plates of a video when its team asked for it.
 *
 * Runs before the previews are made, so the preview shows the blurred picture as well. The
 * untouched original stays next to the blurred copy; from here on the blurred one is the video
 * that gets offered. A video whose team asked for this is never handed on unblurred: when the
 * blurring fails, so does this step.
 */
readonly class CensorVideoStep implements IngestStepInterface
{
    public function __construct(
        private VideoCensorInterface $censor,
        private TeamSettingRepository $teamSettings,
        private VideoRepository $videoRepository,
    ) {
    }

    public function name(): IngestStepEnum
    {
        return IngestStepEnum::CensorVideo;
    }

    public function dependsOn(): array
    {
        return [
            IngestStepEnum::LookupAndUpdateVideoHash,
        ];
    }

    public function isApplicable(IngestContext $context): bool
    {
        if ($context->isDuplicate || $context->isInvalid) {
            return false;
        }

        return $this->isWanted($context->video) && $context->video->source_path === null;
    }

    /**
     * @throws VideoCensorException when the video was to be blurred but could not be
     */
    public function handle(IngestContext $context): IngestContext
    {
        $video = $context->video;
        if ($context->isDuplicate || $context->isInvalid || !$this->isWanted($video)) {
            return $context;
        }

        if (!$this->censor->isAvailable()) {
            throw new VideoCensorException('The video is to be blurred, but blurring is not set up.');
        }

        $disk = $video->getDisk();
        $originalPath = (string)$video->path;
        $blurredPath = $this->blurredPathFor($originalPath);

        $result = $this->censor->censor($disk->path($originalPath), $disk->path($blurredPath));

        $this->videoRepository->update($video, [
            'source_path' => $originalPath,
            'path' => $blurredPath,
            'bytes' => Storage::disk((string)$video->disk)->size($blurredPath),
        ]);

        Log::info('Number plates blurred', [
            'video_id' => $video->getKey(),
            'frames_looked_at' => $result->framesLookedAt,
            'regions_blurred' => $result->regionsBlurred,
        ]);

        return $context;
    }

    /**
     * Whether this video is to be blurred, decided once and then kept on the video.
     */
    private function isWanted(Video $video): bool
    {
        if ($video->censor_requested !== null) {
            return (bool)$video->censor_requested;
        }

        $team = $video->team()->first();
        $wanted = $team !== null
            && (bool)$this->teamSettings->get($team, TeamSettingEnum::CENSOR_LICENSE_PLATES)
            // blurring is on by default, but a team that never asked for it does not get its videos
            // stuck where the tooling is missing; a team that did ask for it does
            && ($this->censor->isAvailable()
                || $this->teamSettings->hasChosen($team, TeamSettingEnum::CENSOR_LICENSE_PLATES));

        // only decide once the team is known, otherwise a video keeps asking on every run
        if ($team !== null) {
            $this->videoRepository->update($video, ['censor_requested' => $wanted]);
        }

        return $wanted;
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
