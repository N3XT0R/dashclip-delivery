<?php

declare(strict_types=1);

use App\Constants\Config\DefaultConfigEntry;
use App\Constants\Config\FFMPEGConfigEntry;

return [
    'labels' => [
        'description' => 'Description',
    ],
    'keys' => [
        'email_admin_mail' => 'Admin email address',
        'email_your_name' => 'Your display name',
        'email_get_bcc_notification' => 'Receive channel notification emails as BCC',
        'email_reminder' => 'Send reminder emails',
        'email_reminder_days' => 'Number of days before expiration for reminder emails',
        'faq_email' => 'Send FAQ email when replying to noreply messages?',
        'expire_after_days' => 'Assignment validity in days',
        'assign_expire_cooldown_days' => 'Cooldown days per (channel, video)',
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
