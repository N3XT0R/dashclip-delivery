<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Mail\Mailables\Envelope;

class UserWelcomeMail extends AbstractLoggedMail
{
    public function __construct(
        public User $user
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Willkommen bei '.config('app.name'),
        );
    }

    protected function viewName(): string
    {
        // TODO: Implement viewName() method.
    }

}