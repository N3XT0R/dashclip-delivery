<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Envelope;

class UserWelcomeMail extends AbstractLoggedMail
{
    public function __construct(
        public User $user,
        public bool $fromBackend = false,
        public ?string $plainPassword = null
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->fromBackend
                ? 'Dein Zugang zu '.config('app.name')
                : 'Willkommen bei '.config('app.name')
        );
    }

    protected function viewName(): string
    {
        return 'emails.user-welcome';
    }

    protected function viewData(): array
    {
        return [
            'user' => $this->user,
            'fromBackend' => $this->fromBackend,
            'plainPassword' => $this->plainPassword,
        ];
    }
}