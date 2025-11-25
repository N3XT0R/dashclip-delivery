<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ChannelPoolDto;
use App\Mail\ChannelWelcomeMail;
use App\Models\Channel;
use App\Models\Video;
use App\Repository\ChannelRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class ChannelService
{
    public function __construct(protected ChannelRepository $channelRepository)
    {
    }

    /**
     * Prepare active channels, rotation pool, and quota mapping.
     * @param  int|null  $quotaOverride
     * @param  array<int|string>  $uploaderIds
     * @return ChannelPoolDto
     */
    public function prepareChannelsAndPool(?int $quotaOverride, array $uploaderIds = []): ChannelPoolDto
    {
        $channels = $this->channelRepository->getActiveChannels();

        $rotationPool = collect();
        foreach ($channels as $channel) {
            $rotationPool = $rotationPool->merge(
                array_fill(0, max(1, (int)$channel->weight), $channel)
            );
        }

        /** @var array<int,int> $quota */
        $quota = $channels
            ->mapWithKeys(fn(Channel $c) => [$c->getKey() => (int)($quotaOverride ?: $c->weekly_quota)])
            ->all();

        $uploaderQuotaMatrix = [];
        $uploaderCount = count($uploaderIds);
        foreach ($quota as $channelId => $channelQuota) {
            if (0 === $uploaderCount) {
                $uploaderQuotaMatrix[$channelId] = [];
                continue;
            }

            $perUploaderQuota = (int)ceil($channelQuota / $uploaderCount);
            foreach ($uploaderIds as $uploaderId) {
                $uploaderQuotaMatrix[$channelId][$uploaderId] = $perUploaderQuota;
            }
        }

        return new ChannelPoolDto(
            channels: $channels,
            rotationPool: $rotationPool,
            channelQuota: $quota,
            uploaderQuotaMatrix: $uploaderQuotaMatrix,
        );
    }

    public function approve(Channel $channel, string $approvalToken): void
    {
        $expected = $channel->getApprovalToken();
        if ($approvalToken !== $expected) {
            throw new InvalidArgumentException('Ungültiger Bestätigungslink.');
        }

        $this->channelRepository->approve($channel);
    }

    /**
     * Get the channels that should receive a welcome mail.
     *
     * This applies the same selection logic that was previously
     * part of the console command, but isolated as pure business logic.
     *
     * @param  string|null  $target  Channel ID or email address (optional)
     * @param  bool  $force  Include already approved channels
     *
     * @return Collection<Channel>
     */
    public function getEligibleForWelcomeMail(?string $target = null, bool $force = false): Collection
    {
        // Targeted by ID or email
        if ($target) {
            if (is_numeric($target)) {
                $channel = $this->channelRepository->findById((int)$target);
            } else {
                $channel = $this->channelRepository->findByEmail($target);
            }

            return $channel ? collect([$channel]) : collect();
        }

        // Default: only unapproved channels unless force is set
        return $force
            ? $this->channelRepository->getActiveChannels()
            : $this->channelRepository->getPendingApproval();
    }

    /**
     * Perform the actual welcome mail sending.
     *
     * The service layer handles the business logic and error handling,
     * while the repository is purely data access.
     *
     * @param  Collection<Channel>  $channels
     * @return array<string> List of email addresses that were successfully processed
     */
    public function sendWelcomeMails(Collection $channels): array
    {
        $sent = [];

        foreach ($channels as $channel) {
            try {
                Mail::to($channel->email)->send(new ChannelWelcomeMail($channel));
                $sent[] = $channel->email;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return $sent;
    }


    /**
     * Wählt einen Zielkanal im Round-Robin über den gewichteten Rotationspool.
     *
     * @param  Collection<int,Video>  $group
     * @param  Collection<int,Channel>  $rotationPool
     * @param  array<int,int>  $channelQuota  (by reference, wird nicht verändert – nur gelesen)
     * @param  array<int,array<int|string,int>>  $uploaderQuota
     * @param  array<int,int>  $blockedChannelIds
     * @param  array<int, Collection<int,int>>  $assignedChannelsByVideo
     * @param  int|string  $uploaderId
     */
    public function pickTargetChannel(
        Collection $group,
        Collection $rotationPool,
        array $channelQuota,
        array $uploaderQuota,
        array $blockedChannelIds,
        array $assignedChannelsByVideo,
        int|string $uploaderId
    ): ?Channel {
        $rotations = 0;
        $poolCount = $rotationPool->count();

        while ($rotations < $poolCount) {
            /** @var Channel $candidate */
            $candidate = $rotationPool->first();
            // rotate
            $rotationPool->push($rotationPool->shift());
            $rotations++;

            // Genügend Quota verfügbar?
            if (($channelQuota[$candidate->getKey()] ?? 0) < $group->count()) {
                continue;
            }

            $uploaderQuotaForChannel = $uploaderQuota[$candidate->getKey()][$uploaderId] ?? 0;
            if ($uploaderQuotaForChannel < $group->count()) {
                continue;
            }

            // Kandidat blockiert?
            if (in_array($candidate->getKey(), $blockedChannelIds, true)) {
                continue;
            }

            // Bereits (irgendwann) an diesen Kanal vergeben?
            $alreadyAssignedToCandidate = $group->some(function (Video $v) use ($candidate, $assignedChannelsByVideo) {
                $assigned = $assignedChannelsByVideo[$v->getKey()] ?? collect();
                return $assigned->contains($candidate->getKey());
            });
            if ($alreadyAssignedToCandidate) {
                continue;
            }

            return $candidate;
        }

        return null;
    }

}