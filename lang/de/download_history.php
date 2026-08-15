<?php

declare(strict_types=1);

return [
    'title' => 'Download-Verlauf',
    'navigation_label' => 'Download-Verlauf',
    'table' => [
        'columns' => [
            'video' => 'Video',
            'channel' => 'Kanal',
            'downloaded_at' => 'Heruntergeladen am',
        ],
        'actions' => [
            'view_video' => 'Video ansehen',
        ],
        'empty_state' => [
            'heading' => 'Noch keine Downloads',
            'description' => 'Sobald ein Kanal eines deiner Videos herunterlädt, erscheint es hier.',
        ],
    ],
];
