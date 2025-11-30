<?php

namespace App\Notifications;

use App\Mail\UserUploadDuplicatedMail;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;

class UserUploadDuplicatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $filename,
        public ?string $note = null
    ) {
    }

    public function via(Model $notifiable): array
    {
        return ['mail', 'database'];
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
            ->body(
                "Die Datei **{$this->filename}** wurde als *Doppeleinsendung* erkannt.".
                ($this->note ? "\n\n{$this->note}" : '')
            )
            ->warning()
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
