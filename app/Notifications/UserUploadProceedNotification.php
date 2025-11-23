<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserUploadProceedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $filename,
        public ?string $note = null
    ) {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Upload verarbeitet: {$this->filename}")
            ->line("Dein Upload wurde erfolgreich verarbeitet.")
            ->lineIf($this->note, $this->note);
    }


    public function toDatabase($notifiable): array
    {
        FilamentNotification::make()
            ->title("Upload verarbeitet")
            ->body("Die Datei **{$this->filename}** wurde erfolgreich bearbeitet.".($this->note ? "\n\n{$this->note}" : ''))
            ->success()
            ->sendToDatabase($notifiable);
    }

    public function toArray($notifiable): array
    {
        return [
            'filename' => $this->filename,
            'note' => $this->note,
        ];
    }
}
