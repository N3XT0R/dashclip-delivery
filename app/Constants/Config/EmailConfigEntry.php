<?php

declare(strict_types=1);

namespace App\Constants\Config;

final readonly class EmailConfigEntry
{
    public const string ADMIN_EMAIL = 'email_admin_mail';
    public const string YOUR_NAME = 'email_your_name';
    public const string GET_BCC_NOTIFICATIONS = 'email_get_bcc_notification';
    public const string REMINDER = 'email_reminder';
    public const string REMINDER_DAYS = 'email_reminder_days';
    public const string FAQ_EMAIL = 'email_faq_email';
}
