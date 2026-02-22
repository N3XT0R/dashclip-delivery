<?php

declare(strict_types=1);

return [
    'channel_application' => [
        'approval_failed_notification' => 'Die Genehmigung des Kanalzugriffs ist fehlgeschlagen.',
    ],

    'video_upload' => [
        'success' => [
            'process_started' => 'Die Verarbeitung kann je nach Video-Größe einige Minuten dauern. Du erhältst eine Benachrichtigung, sobald der Upload abgeschlossen ist.',
        ],
        'error' => [
            'end_sec_must_be_greater' => 'Die Endzeit muss größer als die Startzeit sein.',
            'start_sec_must_be_lower' => 'Die Startzeit muss kleiner als der Endzeitpunkt sein..',
        ],
    ],
];
