<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserUploadProceedMail extends AbstractLoggedMail
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public string $filename,
        public ?string $note = null
    ) {
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Upload verarbeitet: {$this->filename}",
        );
    }

    protected function viewName(): string
    {
        return 'emails.user-upload-proceed';
    }

    protected function viewData(): array
    {
        return [
            'user' => $this->user,
            'filename' => $this->filename,
            'note' => $this->note,
            'date' => now(),
        ];
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
