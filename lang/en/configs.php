<?php

declare(strict_types=1);

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
        'ffmpeg_bin' => 'Path to FFmpeg binary (e.g. /usr/bin/ffmpeg)',
        'ffmpeg_video_codec' => 'Video codec for previews (e.g. libx264)',
        'ffmpeg_audio_codec' => 'Audio codec for previews (e.g. aac)',
        'ffmpeg_preset' => 'FFmpeg preset for speed/quality (e.g. medium)',
        'ffmpeg_crf' => 'CRF quality value 0–51 (e.g. 23)',
        'ffmpeg_video_args' => 'Additional FFmpeg options (e.g. -movflags +faststart)',
        'default_file_system' => 'Default disk for video storage',
    ],
];
