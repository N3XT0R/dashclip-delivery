<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enum\Video\DeliveredVersionEnum;
use App\Mail\VideoVersionSwitchedMail;
use App\Models\Channel;
use App\Models\User;
use App\Models\Video;
use App\Notifications\Contracts\HasToMailContract;
use Illuminate\Bus\Queueable;

/**
 * Tells a channel that the submitter now hands out another version of a video it already has.
 */
class VideoVersionSwitchedNotification extends AbstractUserNotification implements HasToMailContract
{
    use Queueable;

    public function __construct(
        public Video $video,
        public Channel $channel,
        public DeliveredVersionEnum $deliveredVersion,
    ) {
    }

    protected function channels(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): VideoVersionSwitchedMail
    {
        return new VideoVersionSwitchedMail($this->video, $this->channel, $this->deliveredVersion)
            ->to($notifiable->email);
    }
}
