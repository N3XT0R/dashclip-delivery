<?php

return [
    'status' => [
        'progress' => 'Ingest-Fortschritt',
        'progress_label' => ':completed/:total abgeschlossen (:percent%)',
        'current_step' => 'Aktueller Schritt: :step',
        'no_active_step' => 'Kein aktiver Schritt',
        'current' => 'aktuell',
    ],

    'step_status' => [
        'pending' => 'Ausstehend',
        'running' => 'Läuft',
        'completed' => 'Abgeschlossen',
        'failed' => 'Fehlgeschlagen',
    ],

    'steps' => [
        'lookup_and_update_video_hash' => 'Video-Hash berechnen und aktualisieren',
        'generate_preview_for_clips' => 'Vorschau für Clips erzeugen',
        'upload_video_to_dropbox' => 'Video zu Dropbox hochladen',
    ],
];
