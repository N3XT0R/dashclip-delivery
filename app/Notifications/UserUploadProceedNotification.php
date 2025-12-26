<?php

namespace App\Notifications;

use App\Mail\UserUploadProceedMail;
use App\Models\User;
use App\Notifications\Contracts\HasToArrayContract;
use App\Notifications\Contracts\HasToDatabaseContract;
use App\Notifications\Contracts\HasToMailContract;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class UserUploadProceedNotification extends AbstractUserNotification
    implements HasToMailContract, HasToDatabaseContract, HasToArrayContract
{
    use Queueable;

    public function __construct(
        public string $filename,
        public ?string $note = null
    ) {
    }


    /**
     * @param Notifiable&User $notifiable
     * @return UserUploadProceedMail
     */
    public function toMail(User $notifiable): UserUploadProceedMail
    {
        return new UserUploadProceedMail(
            $notifiable,
            $this->filename,
            $this->note
        )->to($notifiable->email);
    }


    /**
     * @param Notifiable&Model $notifiable
     * @return array
     */
    public function toDatabase(Model $notifiable): array
    {
        FilamentNotification::make()
            ->title("Upload verarbeitet")
            ->icon(Heroicon::OutlinedQueueList)
            ->body(
                "Die Datei **{$this->filename}** wurde erfolgreich bearbeitet." . ($this->note ? "\n\n{$this->note}" : '')
            )
            ->success()
            ->sendToDatabase($notifiable)
            ->toBroadcast();

        return $this->toArray($notifiable);
    }

    /**
     * @param Notifiable&Model $notifiable
     * @return array
     */
    public function toArray(Model $notifiable): array
    {
        return [
            'filename' => $this->filename,
            'note' => $this->note,
        ];
    }
}
