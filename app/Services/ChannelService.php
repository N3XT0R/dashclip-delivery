<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Channel\ChannelApplicationRequestDto;
use App\DTO\ChannelPoolDto;
use App\Enum\Channel\ApplicationEnum;
use App\Enum\UploaderTypeEnum;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\User;
use App\Models\Video;
use App\Repository\AssignmentRepository;
use App\Repository\ChannelRepository;
use App\Repository\TeamRepository;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ChannelService
{
    public function __construct(protected ChannelRepository $channelRepository)
    {
    }

    /**
     * Prepare active channels, rotation pool, and remaining weekly quota mapping.
     * @param int|null $quotaOverride
     * @param string $uploaderType
     * @param string|int $uploaderId
     * @return ChannelPoolDto
     */
    public function prepareChannelsAndPool(
        ?int $quotaOverride,
        string $uploaderType,
        string|int $uploaderId,
    ): ChannelPoolDto {
        $teamRepository = app(TeamRepository::class);
        $weeklyAssignmentCounts = $quotaOverride === null
            ? app(AssignmentRepository::class)->countAssignmentsByChannelSince(now()->startOfWeek())
            : collect();
        $channels = $this->channelRepository->getActiveChannels();

        /** @var array<int,int> $quota */
        $quota = $channels
            ->mapWithKeys(fn (Channel $c) => [
                $c->getKey() => max(
                    0,
                    (int)($quotaOverride ?: $c->weekly_quota) - (int)$weeklyAssignmentCounts->get($c->getKey(), 0)
                ),
            ])
            ->all();

        if ($uploaderType === UploaderTypeEnum::TEAM->value) {
            $team = $teamRepository->getTeamByUniqueSlug($uploaderId);

            if ($team) {
                $teamChannels = $this->channelRepository->getTeamAssignedChannels($team);
                $quota = $teamChannels
                    ->mapWithKeys(fn (Channel $channel) => [
                        $channel->getKey() => max(
                            0,
                            (int)$channel->pivot?->quota - (int)$weeklyAssignmentCounts->get($channel->getKey(), 0)
                        ),
                    ])
                    ->all();
                $channels = $teamChannels;
            }
        }

        $channels = $this->orderChannelsForDistribution($channels, $weeklyAssignmentCounts);

        $rotationPool = collect();
        foreach ($channels as $channel) {
            $rotationPool = $rotationPool->merge(
                array_fill(0, max(1, (int)$channel->weight), $channel)
            );
        }

        return new ChannelPoolDto(
            channels: $channels,
            rotationPool: $rotationPool,
            quota: $quota,
        );
    }

    private function orderChannelsForDistribution(Collection $channels, Collection $weeklyAssignmentCounts): Collection
    {
        return $channels
            ->sort(function (Channel $left, Channel $right) use ($weeklyAssignmentCounts): int {
                $leftWeight = max(1, (int)$left->weight);
                $rightWeight = max(1, (int)$right->weight);
                $leftUsageScore = (int)$weeklyAssignmentCounts->get($left->getKey(), 0) / $leftWeight;
                $rightUsageScore = (int)$weeklyAssignmentCounts->get($right->getKey(), 0) / $rightWeight;
                $usageComparison = $leftUsageScore <=> $rightUsageScore;

                if ($usageComparison !== 0) {
                    return $usageComparison;
                }

                $weightComparison = $rightWeight <=> $leftWeight;

                if ($weightComparison !== 0) {
                    return $weightComparison;
                }

                return $left->getKey() <=> $right->getKey();
            })
            ->values();
    }

    /**
     * Get the channels that should receive a welcome mail.
     *
     * This applies the same selection logic that was previously
     * part of the console command, but isolated as pure business logic.
     *
     * @param string|null $target Channel ID or email address (optional)
     * @param bool $force Include already approved channels
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
     * @param Collection<Channel> $channels
     * @return array<string> List of email addresses that were successfully processed
     */
    public function sendWelcomeMails(Collection $channels): array
    {
        $sent = [];
        $mailService = app()->get(MailService::class);

        foreach ($channels as $channel) {
            try {
                $mailService->sendChannelWelcomeMail($channel);
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
     * @param Collection<int,Video> $group
     * @param Collection<int,Channel> $rotationPool
     * @param array<int,int> $quota (by reference, wird nicht verändert – nur gelesen)
     * @param array<int,int> $blockedChannelIds
     * @param array<int, Collection<int,int>> $assignedChannelsByVideo
     */
    public function pickTargetChannel(
        Collection $group,
        Collection $rotationPool,
        array $quota,
        array $blockedChannelIds,
        array $assignedChannelsByVideo
    ): ?Channel {
        $rotations = 0;
        $poolCount = $rotationPool->count();

        while ($rotations < $poolCount) {
            /** @var Channel $candidate */
            $candidate = $rotationPool->first();
            // rotate
            $rotationPool->push($rotationPool->shift());
            $rotations++;

            if ($this->channelAcceptsGroup($candidate, $group, $quota, $blockedChannelIds, $assignedChannelsByVideo)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Whether a specific channel can take the whole group: enough remaining
     * quota, not blocked, and never previously assigned to any video in the
     * group.
     *
     * @param  Collection<int,Video>  $group
     * @param  array<int,int>  $quota
     * @param  array<int,int>  $blockedChannelIds
     * @param  array<int,Collection<int,int>>  $assignedChannelsByVideo
     */
    public function channelAcceptsGroup(
        Channel $channel,
        Collection $group,
        array $quota,
        array $blockedChannelIds,
        array $assignedChannelsByVideo
    ): bool {
        if (($quota[$channel->getKey()] ?? 0) < $group->count()) {
            return false;
        }

        if (in_array($channel->getKey(), $blockedChannelIds, true)) {
            return false;
        }

        $alreadyAssigned = $group->some(function (Video $v) use ($channel, $assignedChannelsByVideo) {
            $assigned = $assignedChannelsByVideo[$v->getKey()] ?? collect();
            return $assigned->contains($channel->getKey());
        });

        return !$alreadyAssigned;
    }

    /**
     * Return the channel instance from the rotation pool matching $channelId,
     * or null when that channel is not part of the (uploader-/team-specific)
     * pool (paused, out of team scope, deleted).
     *
     * @param  Collection<int,Channel>  $rotationPool
     */
    public function findPooledChannel(Collection $rotationPool, int $channelId): ?Channel
    {
        return $rotationPool->first(fn (Channel $channel) => (int) $channel->getKey() === $channelId);
    }

    /**
     * Create a channel access application for the user.
     *
     * @param ChannelApplicationRequestDto $dto
     * @param User $user
     * @return ChannelApplication
     */
    public function applyForAccess(ChannelApplicationRequestDto $dto, User $user): ChannelApplication
    {
        $data = [
            'user_id' => $user->getKey(),
            'channel_id' => $dto->channelId,
            'note' => $dto->note,
            'status' => ApplicationEnum::PENDING->value,
            'meta' => [
                'tos_accepted' => true,
                'tos_accepted_at' => now()->toDateTimeString(),
            ],
        ];
        if (!$dto->otherChannelRequest && $dto->channelId) {
            $existing = $this->channelRepository->hasPendingApplicationForChannel($user, $dto->channelId);

            if ($existing) {
                throw new \DomainException(__('filament.channel_application.messages.error.already_applied'));
            }

            return $this->channelRepository->createApplication($data);
        }

        $data = array_merge_recursive($data, [
            'meta' => [
                'new_channel' => [
                    'name' => $dto->newChannelName,
                    'creator_name' => $dto->newChannelCreatorName,
                    'email' => $dto->newChannelEmail,
                    'youtube_name' => $dto->newChannelYoutubeName,
                ],
            ],
        ]);

        return $this->channelRepository->createApplication($data);
    }

    /**
     * Create a new channel based on the channel application meta data.
     * @param ChannelApplication $application
     * @return Channel
     */
    public function createNewChannelByChannelApplication(ChannelApplication $application): Channel
    {
        $hasNewChannelRequest = filled($application->meta?->channel['name'] ?? null);
        if (!$hasNewChannelRequest) {
            throw new InvalidArgumentException('No new channel request found in application meta.');
        }

        $meta = $application->meta->channel ?? [];
        $channelName = $meta['name'] ?? '';

        if (empty($channelName)) {
            throw new \DomainException('Channel name cannot be empty.');
        }

        if ($this->existsChannelByName(trim($channelName))) {
            throw new \DomainException('A channel with this name already exists.');
        }

        return $this->channelRepository->createChannel([
            'name' => $meta['name'],
            'creator_name' => $meta['creator_name'] ?? 'Unknown Creator',
            'email' => $meta['email'] ?? '',
            'youtube_name' => $meta['youtube_name'] ?? null,
        ]);
    }

    /**
     * Check if a channel exists by its name.
     * @param string $name
     * @return bool
     */
    public function existsChannelByName(string $name): bool
    {
        $channel = $this->channelRepository->findByName($name);
        return $channel !== null;
    }
}
