<?php

declare(strict_types=1);

namespace App\Application\Video;

use App\Constants\Config\DefaultConfigEntry;
use App\Enum\StatusEnum;
use App\Facades\Cfg;
use App\Models\Video;
use App\Repository\ChannelVideoBlockRepository;
use App\Repository\VideoRepository;
use App\Services\Channel\UploadTargetChannelService;
use App\ValueObjects\VideoMarkResult;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Marks videos as deleted once the distribution will never offer them again.
 *
 * That is the case when a video has no open offer left and either a channel downloaded it, or every
 * channel it can reach already had it and no expired offer has a round left. A download ends the
 * rounds right away, but the video stays until that offer's window closes, because the channel may
 * still return it or fetch it again. Their files are removed later by the purge run; offers and
 * downloads stay as history.
 */
readonly class MarkDistributedVideosDeletedUseCase
{
    private const int DEFAULT_ROUNDS = 2;

    public function __construct(
        private VideoRepository $videoRepository,
        private UploadTargetChannelService $uploadTargetChannels,
        private ChannelVideoBlockRepository $channelVideoBlockRepository,
    ) {
    }

    /**
     * @param bool $dryRun count the videos that would be marked without marking them
     * @return VideoMarkResult
     */
    public function handle(bool $dryRun = false): VideoMarkResult
    {
        $rounds = $this->configuredRounds();
        $marked = 0;

        foreach ($this->videoRepository->lazyWithoutOpenOffers() as $video) {
            if (!$this->isDistributionFinished($video, $rounds)) {
                continue;
            }

            $marked++;

            if (!$dryRun) {
                $this->markDeleted($video);
            }
        }

        return new VideoMarkResult($marked);
    }

    /**
     * Whether the video will never be offered again. Only videos without an open offer get here,
     * so a downloaded offer has already run out of its window by now.
     *
     * @param Video $video
     * @param int $rounds
     * @return bool
     */
    private function isDistributionFinished(Video $video, int $rounds): bool
    {
        $assignments = $video->assignments()->get();

        if ($assignments->contains('status', StatusEnum::PICKEDUP->value)) {
            return true;
        }

        $hasFreeRound = $assignments->contains(
            fn ($assignment): bool => $assignment->status === StatusEnum::EXPIRED->value
                && (int)$assignment->offer_round < $rounds
        );

        if ($hasFreeRound) {
            return false;
        }

        return $this->openChannelIds($video, $assignments->pluck('channel_id'))->isEmpty();
    }

    /**
     * Channels the video could still be offered to: reachable, not served yet and not blocking it.
     *
     * @param Video $video
     * @param Collection<int, int> $servedChannelIds
     * @return Collection<int, int>
     */
    private function openChannelIds(Video $video, Collection $servedChannelIds): Collection
    {
        $blocked = collect($this->channelVideoBlockRepository->preloadActiveBlocks(collect([$video]))[
            $video->getKey()
        ] ?? []);

        return $this->uploadTargetChannels
            ->getSelectableChannels($video->team()->first())
            ->map(fn ($channel): int => (int)$channel->getKey())
            ->diff($servedChannelIds->map(fn ($id): int => (int)$id)->diff($blocked))
            ->values();
    }

    private function markDeleted(Video $video): void
    {
        DB::transaction(function () use ($video): void {
            $locked = $this->videoRepository->lockForUpdate((int)$video->getKey());
            if ($locked === null) {
                return;
            }

            $this->videoRepository->delete($locked);
        });
    }

    private function configuredRounds(): int
    {
        return max(1, (int)Cfg::get(DefaultConfigEntry::DISTRIBUTION_ROUNDS, 'default', self::DEFAULT_ROUNDS, true));
    }
}
