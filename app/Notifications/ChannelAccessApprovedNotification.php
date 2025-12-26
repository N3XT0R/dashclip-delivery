<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\AbstractLoggedMail;
use App\Mail\ChannelAccessApprovedMail;
use App\Models\ChannelApplication;
use App\Models\User;
use App\Notifications\Contracts\HasToDatabaseContract;
use App\Notifications\Contracts\HasToMailContract;
use Illuminate\Database\Eloquent\Model;

class ChannelAccessApprovedNotification extends AbstractUserNotification
    implements HasToMailContract,
               HasToDatabaseContract
{
    protected bool $isConfigurable = false;

    public function __construct(
        public readonly ChannelApplication $channelApplication
    ) {
    }

    public function toDatabase(Model $notifiable): array
    {
        return [
            'application' => $this->channelApplication,
            'channel' => $this->channelApplication->channel,
        ];
    }

    public function toMail(User $notifiable): AbstractLoggedMail
    {
        return new ChannelAccessApprovedMail($this->channelApplication)->to($notifiable);
    }

}
