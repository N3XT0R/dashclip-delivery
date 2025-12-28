<?php

declare(strict_types=1);

namespace App\Services;

use App\Enum\{NotificationTypeEnum, StatusEnum};
use App\Models\{Assignment, Channel, Notification};
use Illuminate\Support\Collection;

/**
 * @deprecated no replacement
 */
class ReminderNotifier
{
    /**
     * Send reminder emails for assignments expiring in given days.
     *
     * @return array{sent:int}
     */
    public function notify(int $days = 1): array
    {
        $start = today();
        $end = today()->addDays($days + 1);

        $assignments = Assignment::query()
            ->where('status', StatusEnum::NOTIFIED->value)
            ->where('expires_at', '>=', $start)
            ->where('expires_at', '<', $end)
            ->with('video.clips')
            ->get()
            ->groupBy('channel_id');

        $sent = 0;
        foreach ($assignments as $channelId => $items) {
            $channel = Channel::query()->find($channelId);
            if (null === $channel) {
                continue;
            }
            $this->notifyChannel($channel, $items);
            $sent++;
        }

        return ['sent' => $sent];
    }

    public function notifyChannel(Channel $channel, Collection $assignments): void
    {
        /**
         * @var Notification $notification
         * @deprecated will be removed in next major release
         * @note replaced by mail notifications
         */
        $notification = Notification::query()->create([
            'channel_id' => $channel->getKey(),
            'type' => NotificationTypeEnum::REMINDER->value,
        ]);

        foreach ($assignments as $assignment) {
            $assignment->setAttribute('notification_id', $notification->getKey());
            $assignment->save();
        }

        app(MailService::class)->sendReminderMail($channel, $assignments);
    }
}
