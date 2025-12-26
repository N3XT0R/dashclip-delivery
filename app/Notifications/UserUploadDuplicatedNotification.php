<?php

namespace App\Notifications;

use App\Mail\UserUploadDuplicatedMail;
use App\Models\User;
use App\Notifications\Contracts\HasToArrayContract;
use App\Notifications\Contracts\HasToDatabaseContract;
use App\Notifications\Contracts\HasToMailContract;
use Filament\Notifications\Notification as FilamentNotification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Model;

class UserUploadDuplicatedNotification extends AbstractUserNotification
    implements HasToMailContract,
               HasToDatabaseContract,
               HasToArrayContract
{
    use Queueable;

    public function __construct(
        public string $filename,
        public ?string $note = null
    ) {
    }

    public function toMail(User $notifiable): UserUploadDuplicatedMail
    {
        return new UserUploadDuplicatedMail(
            user: $notifiable,
            filename: $this->filename,
            note: $this->note
        )->to($notifiable->email);
    }

    public function toDatabase(Model $notifiable): array
    {
        FilamentNotification::make()
            ->title("Upload verarbeitet")
            ->icon(Heroicon::OutlinedQueueList)
            ->body(
                "Die Datei **{$this->filename}** wurde als *Doppeleinsendung* erkannt." .
                ($this->note ? "\n\n{$this->note}" : '')
            )
            ->danger()
            ->sendToDatabase($notifiable)
            ->toBroadcast();

        return $this->toArray($notifiable);
    }

    public function toArray(Model $notifiable): array
    {
        return [
            'filename' => $this->filename,
            'note' => $this->note,
        ];
    }
}
