<?php

declare(strict_types=1);
return [
    'title' => 'Kanaleinstellungen',
    'channel_resource' => [
        'creator_name' => 'Name des Creators',
        'relation_manager' => [
            'users' => [
                'title' => 'Zugewiesene Benutzer',
                'columns' => [
                    'channel' => 'Kanal',
                    'email' => 'E-Mail',
                    'verified' => 'Verifiziert',
                ],
            ],
        ],
    ],
];
