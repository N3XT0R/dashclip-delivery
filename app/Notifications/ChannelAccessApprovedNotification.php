<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\AbstractLoggedMail;
use App\Mail\ChannelAccessApprovedMail;
use App\Models\ChannelApplication;
use App\Models\User;
use App\Notifications\Contracts\HasToArrayContract;
use App\Notifications\Contracts\HasToDatabaseContract;
use App\Notifications\Contracts\HasToMailContract;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Model;

class ChannelAccessApprovedNotification extends AbstractUserNotification
    implements HasToMailContract,
               HasToDatabaseContract,
               HasToArrayContract
{
    protected bool $isConfigurable = false;

    public function __construct(
        public readonly ChannelApplication $channelApplication
    ) {
    }

    public function toDatabase(Model $notifiable): array
    {
        FilamentNotification::make()
            ->title(__('notifications.channel_access_approved.title'))
            ->icon(Heroicon::OutlinedQueueList)
            ->body(
                __('notifications.channel_access_approved.body', [
                    'channelName' => $this->channelApplication->channel->name,
                ])
            )
            ->success()
            ->sendToDatabase($notifiable)
            ->toBroadcast();

        return $this->toArray($notifiable);
    }

    public function toMail(User $notifiable): AbstractLoggedMail
    {
        return new ChannelAccessApprovedMail($this->channelApplication)->to($notifiable);
    }

    public function toArray(Model $notifiable): array
    {
        return [
            'application' => $this->channelApplication->getKey(),
            'channel' => $this->channelApplication->channel->getKey(),
        ];
    }


}
