<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
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
     * @param  Notifiable&Model  $notifiable
     * @return MailMessage
     */
    public function toMail(Model $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Upload verarbeitet: {$this->filename}")
            ->line("Dein Upload wurde erfolgreich verarbeitet.")
            ->lineIf($this->note, $this->note);
    }


    /**
     * @param  Notifiable&Model  $notifiable
     * @return array
     */
    public function toDatabase(Model $notifiable): array
    {
        FilamentNotification::make()
            ->title("Upload verarbeitet")
            ->body("Die Datei **{$this->filename}** wurde erfolgreich bearbeitet.".($this->note ? "\n\n{$this->note}" : ''))
            ->success()
            ->sendToDatabase($notifiable);

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
