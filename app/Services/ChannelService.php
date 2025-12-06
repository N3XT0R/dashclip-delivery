<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Channel\ChannelApplicationRequestDto;
use App\DTO\ChannelPoolDto;
use App\Enum\Channel\ApplicationEnum;
use App\Mail\ChannelWelcomeMail;
use App\Models\Channel;
use App\Models\ChannelApplication;
use App\Models\Team;
use App\Models\User;
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
     * @param  string  $uploaderType
     * @param  string|int  $uploaderId
     * @return ChannelPoolDto
     */
    public function prepareChannelsAndPool(
        ?int $quotaOverride,
        string $uploaderType,
        string|int $uploaderId,
    ): ChannelPoolDto {
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

        if ($uploaderType === 'team') {
            $team = Team::query()->where('slug', $uploaderId)->first();

            if ($team) {
                $teamChannels = $team->assignedChannels()->get();

                $teamQuotas = $teamChannels
                    ->mapWithKeys(fn(Channel $channel) => [$channel->getKey() => (int)$channel->pivot->quota])
                    ->all();

                foreach ($teamQuotas as $channelId => $teamQuota) {
                    $quota[$channelId] = $teamQuota;
                }
            }
        }

        return new ChannelPoolDto(
            channels: $channels,
            rotationPool: $rotationPool,
            quota: $quota,
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
     * @param  array<int,int>  $quota  (by reference, wird nicht verändert – nur gelesen)
     * @param  array<int,int>  $blockedChannelIds
     * @param  array<int, Collection<int,int>>  $assignedChannelsByVideo
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

            // Genügend Quota verfügbar?
            if (($quota[$candidate->getKey()] ?? 0) < $group->count()) {
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

    /**
     * Create a channel access application for the user.
     *
     * @param  ChannelApplicationRequestDto  $dto
     * @param  User  $user
     * @return ChannelApplication
     */
    public function applyForAccess(ChannelApplicationRequestDto $dto, User $user): ChannelApplication
    {
        $data = [
            'user_id' => $user->getKey(),
            'channel_id' => $dto->channelId,
            'note' => $dto->note,
            'status' => ApplicationEnum::PENDING->value,
        ];
        if (!$dto->otherChannelRequest && $dto->channelId) {
            $existing = $user->channelApplications()
                ->where('channel_id', $dto->channelId)
                ->whereIn('status', ['pending', 'approved'])
                ->first();

            if ($existing) {
                throw new \DomainException(__('You have already applied for this channel.'));
            }

            return $this->channelRepository->createApplication($data);
        }

        $data = array_merge($data, [
            'meta' => json_encode([
                'new_channel' => [
                    'name' => $dto->newChannelName,
                    'creator_name' => $dto->newChannelCreatorName,
                    'email' => $dto->newChannelEmail,
                    'youtube_name' => $dto->newChannelYoutubeName,
                ],
                'tos_accepted' => true,
                'tos_accepted_at' => now()->toDateTimeString(),
            ]),
        ]);

        return $this->channelRepository->createApplication($data);
    }
}