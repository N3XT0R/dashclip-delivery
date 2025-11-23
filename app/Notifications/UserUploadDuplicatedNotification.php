<?php

namespace App\Notifications;

use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserUploadDuplicatedNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $filename,
        public ?string $note = null
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Upload verarbeitet: {$this->filename}")
            ->line("Dein Upload ist eine Doppeleinsendung!.")
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

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'filename' => $this->filename,
            'note' => $this->note,
        ];
    }
}
