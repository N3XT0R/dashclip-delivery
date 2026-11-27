<?php

declare(strict_types=1);


use App\Constants\Config\DefaultConfigEntry;
use App\Constants\Config\CensorConfigEntry;
use App\Constants\Config\EmailConfigEntry;
use App\Constants\Config\FFMPEGConfigEntry;
use App\Constants\Config\PrivacyConfigEntry;

return [
    'labels' => [
        'description' => 'Beschreibung',
    ],
    'censor' => [
        'switched_off' => 'Die Unkenntlichmachung ist derzeit abgeschaltet. Setze „Kennzeichen unkenntlich machen" auf an, damit neue Videos wieder bearbeitet werden.',
        'not_installed' => 'Die Unkenntlichmachung ist auf diesem Server nicht eingerichtet. Diese Werte werden gespeichert, wirken sich aber erst aus, sobald die Werkzeuge installiert und freigeschaltet sind.',
    ],
    'keys' => [
        CensorConfigEntry::ENABLED => 'Kennzeichen unkenntlich machen (schaltet die gesamte Verarbeitung an und aus)',
        CensorConfigEntry::COLUMNS => 'Spalten der Erkennungskacheln (1 bis 8)',
        CensorConfigEntry::ROWS => 'Zeilen der Erkennungskacheln (1 bis 8)',
        CensorConfigEntry::FRAME_STEP => 'Jedes wievielte Bild geprüft wird (1 bis 60)',
        CensorConfigEntry::CONFIDENCE => 'Erkennungsschwelle (0,01 bis 1; kleiner erkennt mehr)',
        CensorConfigEntry::MARGIN => 'Zusätzlicher Rand je Fund (0 bis 1; 0,25 entspricht 25 %)',
        CensorConfigEntry::TIMEOUT => 'Zeitlimit je Video in Sekunden (1 bis 3600)',
        CensorConfigEntry::THREADS => 'Rechenfäden für die Erkennung (1 bis 16; einen Kern für die Videoausgabe frei lassen)',
        CensorConfigEntry::SEARCH_FROM => 'Ab welcher Bildhöhe gesucht wird (0 bis 0,9; nur zusammen mit Kachelzeilen 1 sinnvoll)',
        PrivacyConfigEntry::STORAGE_PROVIDER_NAME => 'Datenschutz: Name des Speicher-Anbieters (Auftragsverarbeiter), z. B. mit Serverstandort',
        EmailConfigEntry::ADMIN_EMAIL => 'Admin-Mail-Adresse',
        EmailConfigEntry::YOUR_NAME => 'Dein angezeigter Name',
        EmailConfigEntry::GET_BCC_NOTIFICATIONS => 'Channel-Notification Emails als BCC empfangen',
        EmailConfigEntry::REMINDER => 'Erinnerungsmails verschicken',
        EmailConfigEntry::REMINDER_DAYS => 'Anzahl der Tage vor Ablauf für Erinnerungs-E-Mails',
        EmailConfigEntry::FAQ_EMAIL => 'FAQ-Email verschicken wenn auf noreply Nachrichten geantwortet wird?',
        DefaultConfigEntry::EXPIRE_AFTER_DAYS => 'Assignment Gültigkeit in Tagen',
        DefaultConfigEntry::ASSIGN_EXPIRE_COOLDOWN_DAYS => 'Cooldown-Tage je (channel, video)',
        DefaultConfigEntry::INGEST_INBOX_ABSOLUTE_PATH => 'Inbox-Pfad für Videos (absolut)',
        DefaultConfigEntry::POST_EXPIRY_RETENTION_WEEKS => 'Aufbewahrungsfrist gelöschter Videos (in Wochen)',
        DefaultConfigEntry::DISTRIBUTION_ROUNDS => 'Angebotsrunden je Video',
        FFMPEGConfigEntry::BINARY => 'Pfad zur FFmpeg-Binärdatei (z.B. /usr/bin/ffmpeg)',
        FFMPEGConfigEntry::VIDEO_CODEC => 'Video-Codec für Previews (z.B. libx264)',
        FFMPEGConfigEntry::AUDIO_CODEC => 'Audio-Codec für Previews (z.B. aac)',
        FFMPEGConfigEntry::PRESET => 'FFmpeg-Preset für Geschwindigkeit/Qualität (z.B. medium)',
        FFMPEGConfigEntry::CRF => 'CRF-Qualitätswert 0–51 (z.B. 23)',
        FFMPEGConfigEntry::VIDEO_ARGS => 'Zusätzliche FFmpeg-Optionen (z.B. -movflags +faststart)',
        DefaultConfigEntry::DEFAULT_FILE_SYSTEM => 'Standard-Disk für Videospeicherung',
    ],
];
