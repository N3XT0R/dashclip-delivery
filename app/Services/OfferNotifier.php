<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\{BatchTypeEnum, StatusEnum};
use App\Models\{Assignment, Batch, Channel};
use App\Services\Channel\ChannelOperatorService;
use Carbon\CarbonInterface;

class OfferNotifier
{

    public function __construct(
        private BatchService $batchService,
        private ChannelOperatorService $channelOperatorService
    ) {
    }

    /**
     * Notify channels about new offers and return stats.
     *
     * @return array{sent:int,batchId:int}
     */
    public function notify(int $ttlDays, ?Batch $assignBatch = null): array
    {
        $expireDate = now()->addDays($ttlDays);
        if (null === $assignBatch) {
            $assignBatch = $this->batchService->getLatestAssignBatch();
        }

        $channelIds = Assignment::query()->where('batch_id', $assignBatch->getKey())
            ->whereIn('status', StatusEnum::getReadyStatus())
            ->pluck('channel_id')->unique()->values();

        if ($channelIds->isEmpty()) {
            return ['sent' => 0, 'batchId' => $assignBatch->getKey()];
        }

        $sent = 0;

        $channels = Channel::query()->whereIn('id', $channelIds)->get();
        foreach ($channels as $channel) {
            $this->notifyChannel($channel, $assignBatch, $expireDate);
            $sent++;
        }

        Batch::query()->create([
            'type' => BatchTypeEnum::NOTIFY->value,
            'started_at' => now(),
            'finished_at' => now(),
            'stats' => ['emails' => $sent]
        ]);

        return ['sent' => $sent, 'batchId' => $assignBatch->getKey()];
    }

    public function notifyChannel(Channel $channel, Batch $assignBatch, CarbonInterface $expireDate): void
    {
        $assignments = Assignment::query()
            ->where('batch_id', $assignBatch->getKey())
            ->where('channel_id', $channel->getKey())
            ->get();

        if ($assignments->isEmpty()) {
            return;
        }


        $isOperator = $this->channelOperatorService->isChannelEmailOwnerChannelOperator($channel);
        app(MailService::class)->sendNewOfferMail($channel, $assignBatch, $expireDate, $isOperator);
    }
}

