<?php

declare(strict_types=1);

namespace App\Support\Mail;

use App\Models\User;

final class MailAddressResolver
{
    /**
     * Resolve a safe mail recipient address.
     *
     * @param User|string|null $recipient
     * @return string
     */
    public function resolve(User|string|null $recipient): string
    {
        $email = $recipient instanceof User
            ? $recipient->getRawOriginal('email')
            : $recipient;

        return $this->applyCatchAll($email);
    }

    /**
     * Apply catch-all address in non-production environments.
     *
     * @param string|null $email
     * @return string
     */
    private function applyCatchAll(?string $email): string
    {
        if (app()->environment('local', 'testing', 'staging')) {
            return (string)(config('mail.catch_all') ?? $email);
        }

        return (string)$email;
    }
}
