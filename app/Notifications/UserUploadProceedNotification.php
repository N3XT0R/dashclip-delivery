<?php

namespace App\Notifications;

use App\Mail\UserUploadProceedMail;
use App\Models\User;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;

class UserUploadProceedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $filename,
        public ?string $note = null
    ) {
    }

    /**
     * @param  Notifiable&Model  $notifiable
     * @return array
     */
    public function via(Model $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * @param  Notifiable&User  $notifiable
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
     * @param  Notifiable&Model  $notifiable
     * @return array
     */
    public function toDatabase(Model $notifiable): array
    {
        $notification = FilamentNotification::make()
            ->title("Upload verarbeitet")
            ->body("Die Datei **{$this->filename}** wurde erfolgreich bearbeitet.".($this->note ? "\n\n{$this->note}" : ''))
            ->success()
            ->sendToDatabase($notifiable)
            ->broadcast($notifiable);

        return $this->toArray($notifiable);
    }

    /**
     * @param  Notifiable&Model  $notifiable
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
