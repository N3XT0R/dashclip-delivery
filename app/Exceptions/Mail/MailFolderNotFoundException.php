<?php

declare(strict_types=1);

namespace App\Exceptions\Mail;

final class MailFolderNotFoundException extends MailException
{
    /**
     * Report that an expected mailbox folder is missing on the server.
     *
     * @param string $path Folder path that was requested, for example "INBOX".
     * @param string|null $account Configured mailbox account identifier, null for the default account.
     * @return self
     */
    public static function forPath(string $path, ?string $account = null): self
    {
        return new self(
            sprintf('Mailbox folder "%s" does not exist for account "%s".', $path, $account ?? 'default')
        );
    }
}
