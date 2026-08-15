<?php

declare(strict_types=1);

return [
    'title' => 'Download History',
    'navigation_label' => 'Download History',
    'table' => [
        'columns' => [
            'video' => 'Video',
            'channel' => 'Channel',
            'downloaded_at' => 'Downloaded at',
        ],
        'actions' => [
            'view_video' => 'View video',
        ],
        'empty_state' => [
            'heading' => 'No downloads yet',
            'description' => 'Once a channel downloads one of your videos, it will show up here.',
        ],
    ],
];
