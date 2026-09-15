<?php

declare(strict_types=1);

namespace App\Exceptions\Mail;

use Throwable;

final class MailConnectionException extends MailException
{
    /**
     * Wrap an infrastructure failure that prevented the mailbox from being reached.
     *
     * @param string|null $account Configured mailbox account identifier, null for the default account.
     * @param Throwable $previous Original transport failure, preserved for debugging.
     * @return self
     */
    public static function forAccount(?string $account, Throwable $previous): self
    {
        return new self(
            sprintf('Could not reach the mailbox for account "%s".', $account ?? 'default'),
            0,
            $previous
        );
    }
}
