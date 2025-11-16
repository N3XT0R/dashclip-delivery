<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\ChannelPoolDto;
use App\Mail\ChannelWelcomeMail;
use App\Models\Channel;
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
     * @return ChannelPoolDto
     */
    public function prepareChannelsAndPool(?int $quotaOverride): ChannelPoolDto
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

}