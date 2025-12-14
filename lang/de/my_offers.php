<?php

declare(strict_types=1);

return [
    'title' => 'Meine Angebote',
    'navigation_label' => 'Meine Angebote',
    'navigation_group' => 'Media',

    'tabs' => [
        'available' => 'Verfügbar',
        'downloaded' => 'Heruntergeladen',
        'expired' => 'Abgelaufen',
        'returned' => 'Zurückgewiesen',
    ],

    'stats' => [
        'available' => [
            'label' => 'Verfügbare Angebote',
            'downloaded_from_available' => 'Bereits geladen',
            'avg_validity_days' => 'Ø Gültigkeit (Tage)',
        ],
        'downloaded' => [
            'label' => 'Heruntergeladen',
            'total' => 'Gesamt',
            'avg_download_days_ago' => 'Ø vor Tagen',
        ],
        'expired' => [
            'label' => 'Abgelaufen',
            'total' => 'Gesamt',
            'downloaded_count' => 'Davon geladen',
            'missed_count' => 'Verpasst',
        ],
    ],

    'table' => [
        'columns' => [
            'video_title' => 'Video',
            'uploader' => 'Von Uploader',
            'valid_until' => 'Gültig bis',
            'remaining_days' => 'Noch :days Tage',
            'remaining_hours' => 'Noch :hours Stunden',
            'status' => 'Status',
            'offered_at' => 'Angeboten am',
            'downloaded_at' => 'Heruntergeladen am',
            'expired_at' => 'Abgelaufen am',
            'returned_at' => 'Zurückgewiesen am',
            'was_downloaded' => 'Heruntergeladen?',
            'return_reason' => 'Grund',
        ],
        'status_badges' => [
            'available' => 'Verfügbar',
            'downloaded' => 'Heruntergeladen',
            'yes' => 'Ja',
            'no' => 'Nein',
        ],
        'actions' => [
            'view_details' => 'Details',
            'download' => 'Herunterladen',
            'download_again' => 'Erneut laden',
        ],
        'bulk_actions' => [
            'download_all' => 'Alle herunterladen',
            'download_selected' => 'Auswahl herunterladen (:count)',
        ],
        'empty_state' => [
            'heading' => 'Keine Angebote vorhanden',
            'description' => 'Sobald Videos für Sie verfügbar sind, erscheinen sie hier.',
        ],
    ],

    'modal' => [
        'title' => 'Video-Details',
        'metadata' => [
            'heading' => 'Metadaten',
            'file_size' => 'Dateigröße',
            'duration' => 'Länge',
            'filename' => 'Dateiname',
        ],
        'clips' => [
            'heading' => 'Clip-Informationen',
            'role' => 'Rolle',
            'timing' => 'Timing',
            'submitter' => 'Einsender',
            'notes' => 'Notizen',
            'no_clips' => 'Keine Clips vorhanden',
        ],
        'preview' => [
            'heading' => 'Vorschau',
            'not_available' => 'Keine Vorschau verfügbar',
        ],
    ],
];
