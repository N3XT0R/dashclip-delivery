<?php

declare(strict_types=1);

return [
    'processing_status' => [
        'pending' => 'Wartend',
        'processing' => 'In Verarbeitung',
        'completed' => 'Abgeschlossen',
        'failed' => 'Fehlgeschlagen',
        'deleted' => 'Gelöscht',
        'unknown' => 'Unbekannt',
    ],
    'assignment_state' => [
        'downloaded' => 'Heruntergeladene Offers',
        'active' => 'Nur aktive Offers',
        'expired' => 'Abgelaufene Offers',
        'all' => 'Alle',
    ],
    'distribution_status' => [
        'available' => 'Verfügbar',
        'downloaded' => 'Heruntergeladen',
        'expired' => 'Abgelaufen',
        'all_distributed' => 'Alle verteilt',
        'preparing' => 'In Vorbereitung',
    ],
];