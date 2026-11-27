<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Mail\AbstractLoggedMail;
use App\Mail\OfferDownloadReadyMail;
use App\Models\Channel;
use App\Models\User;
use App\Notifications\Contracts\HasToArrayContract;
use App\Notifications\Contracts\HasToMailContract;
use App\Repository\ChannelRepository;
use Filament\Actions\Exports\Models\Export;
use Illuminate\Database\Eloquent\Model;

/**
 * Mails the link of a prepared offer download; the in-app notification already carries the button.
 */
class OfferDownloadReadyNotification extends AbstractUserNotification implements
    HasToMailContract,
    HasToArrayContract
{
    public function __construct(
        public readonly Export $export,
        public readonly string $authGuard,
        public readonly ?Channel $channel,
    ) {
    }

    /**
     * Only channel operators prepare offer downloads, so only they get to choose.
     * @param User $user
     * @return bool
     */
    public static function isVisibleFor(User $user): bool
    {
        return app(ChannelRepository::class)->hasUserAccessToAnyChannel($user);
    }

    protected function channels(): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): AbstractLoggedMail
    {
        return (new OfferDownloadReadyMail($this->export, $this->authGuard, $this->channel))->to($notifiable);
    }

    public function toArray(Model $notifiable): array
    {
        return [
            'export' => $this->export->getKey(),
            'channel' => $this->channel?->getKey(),
        ];
    }
}
