<?php

declare(strict_types=1);

namespace App\Constants\Config;

final readonly class DefaultConfigEntry
{
    public const string DEFAULT_FILE_SYSTEM = 'default_file_system';
    public const string ASSIGN_EXPIRE_COOLDOWN_DAYS = 'assign_expire_cooldown_days';
    public const string EXPIRE_AFTER_DAYS = 'expire_after_days';
    public const string POST_EXPIRY_RETENTION_WEEKS = 'post_expiry_retention_weeks';
    public const string INGEST_INBOX_ABSOLUTE_PATH = 'ingest_inbox_absolute_path';
}
