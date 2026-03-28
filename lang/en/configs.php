<?php

declare(strict_types=1);

use App\Constants\Config\DefaultConfigEntry;
use App\Constants\Config\EmailConfigEntry;
use App\Constants\Config\FFMPEGConfigEntry;

return [
    'labels' => [
        'description' => 'Description',
    ],
    'keys' => [
        EmailConfigEntry::ADMIN_EMAIL => 'Admin email address',
        EmailConfigEntry::YOUR_NAME => 'Your display name',
        EmailConfigEntry::GET_BCC_NOTIFICATIONS => 'Receive channel notification emails as BCC',
        EmailConfigEntry::REMINDER => 'Send reminder emails',
        EmailConfigEntry::REMINDER_DAYS => 'Number of days before expiration for reminder emails',
        EmailConfigEntry::FAQ_EMAIL => 'Send FAQ email when replying to noreply messages?',
        DefaultConfigEntry::EXPIRE_AFTER_DAYS => 'Assignment validity in days',
        DefaultConfigEntry::ASSIGN_EXPIRE_COOLDOWN_DAYS => 'Cooldown days per (channel, video)',
        'ingest_inbox_absolute_path' => 'Inbox path for videos (absolute)',
        'post_expiry_retention_weeks' => 'Retention period after expiration (in weeks)',
        FFMPEGConfigEntry::BINARY => 'Path to FFmpeg binary (e.g. /usr/bin/ffmpeg)',
        FFMPEGConfigEntry::VIDEO_CODEC => 'Video codec for previews (e.g. libx264)',
        FFMPEGConfigEntry::AUDIO_CODEC => 'Audio codec for previews (e.g. aac)',
        FFMPEGConfigEntry::PRESET => 'FFmpeg preset for speed/quality (e.g. medium)',
        FFMPEGConfigEntry::CRF => 'CRF quality value 0–51 (e.g. 23)',
        FFMPEGConfigEntry::VIDEO_ARGS => 'Additional FFmpeg options (e.g. -movflags +faststart)',
        DefaultConfigEntry::DEFAULT_FILE_SYSTEM => 'Default disk for video storage',
    ],
];
