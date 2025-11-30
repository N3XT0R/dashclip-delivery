<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class UserUploadDuplicatedMail extends AbstractLoggedMail
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $filename,
        public ?string $note = null,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Upload verarbeitet: {$this->filename}",
        );
    }

    protected function viewName(): string
    {
        return 'emails.upload-duplicated';
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
}
