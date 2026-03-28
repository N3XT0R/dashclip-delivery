<?php

declare(strict_types=1);


use App\Constants\Config\DefaultConfigEntry;
use App\Constants\Config\FFMPEGConfigEntry;

return [
    'labels' => [
        'description' => 'Beschreibung',
    ],
    'keys' => [
        'email_admin_mail' => 'Admin-Mail-Adresse',
        'email_your_name' => 'Dein angezeigter Name',
        'email_get_bcc_notification' => 'Channel-Notification Emails als BCC empfangen',
        'email_reminder' => 'Erinnerungsmails verschicken',
        'email_reminder_days' => 'Anzahl der Tage vor Ablauf für Erinnerungs-E-Mails',
        'faq_email' => 'FAQ-Email verschicken wenn auf noreply Nachrichten geantwortet wird?',
        'expire_after_days' => 'Assignment Gültigkeit in Tagen',
        'assign_expire_cooldown_days' => 'Cooldown-Tage je (channel, video)',
        'ingest_inbox_absolute_path' => 'Inbox-Pfad für Videos (absolut)',
        'post_expiry_retention_weeks' => 'Aufbewahrungsfrist nach Ablauf (in Wochen)',
        FFMPEGConfigEntry::BINARY => 'Pfad zur FFmpeg-Binärdatei (z.B. /usr/bin/ffmpeg)',
        FFMPEGConfigEntry::VIDEO_CODEC => 'Video-Codec für Previews (z.B. libx264)',
        FFMPEGConfigEntry::AUDIO_CODEC => 'Audio-Codec für Previews (z.B. aac)',
        FFMPEGConfigEntry::PRESET => 'FFmpeg-Preset für Geschwindigkeit/Qualität (z.B. medium)',
        FFMPEGConfigEntry::CRF => 'CRF-Qualitätswert 0–51 (z.B. 23)',
        FFMPEGConfigEntry::VIDEO_ARGS => 'Zusätzliche FFmpeg-Optionen (z.B. -movflags +faststart)',
        DefaultConfigEntry::DEFAULT_FILE_SYSTEM => 'Standard-Disk für Videospeicherung',
    ],
];
