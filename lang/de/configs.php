<?php

declare(strict_types=1);


use App\Constants\Config\DefaultConfigEntry;
use App\Constants\Config\EmailConfigEntry;
use App\Constants\Config\FFMPEGConfigEntry;

return [
    'labels' => [
        'description' => 'Beschreibung',
    ],
    'keys' => [
        EmailConfigEntry::ADMIN_EMAIL => 'Admin-Mail-Adresse',
        EmailConfigEntry::YOUR_NAME => 'Dein angezeigter Name',
        EmailConfigEntry::GET_BCC_NOTIFICATIONS => 'Channel-Notification Emails als BCC empfangen',
        EmailConfigEntry::REMINDER => 'Erinnerungsmails verschicken',
        EmailConfigEntry::REMINDER_DAYS => 'Anzahl der Tage vor Ablauf für Erinnerungs-E-Mails',
        EmailConfigEntry::FAQ_EMAIL => 'FAQ-Email verschicken wenn auf noreply Nachrichten geantwortet wird?',
        DefaultConfigEntry::EXPIRE_AFTER_DAYS => 'Assignment Gültigkeit in Tagen',
        DefaultConfigEntry::ASSIGN_EXPIRE_COOLDOWN_DAYS => 'Cooldown-Tage je (channel, video)',
        DefaultConfigEntry::INGEST_INBOX_ABSOLUTE_PATH => 'Inbox-Pfad für Videos (absolut)',
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
