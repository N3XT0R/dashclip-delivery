<?php

declare(strict_types=1);
return [
    'title' => 'Kanaleinstellungen',
    'channel_resource' => [
        'creator_name' => 'Name des Creators',
        'show_on_homepage' => 'Auf der Startseite anzeigen',
        'show_on_homepage_hint' => 'Ist die Anzeige aktiv, erscheint dein Kanal in der öffentlichen Kanalliste auf der Startseite.',
        'logo' => 'Logo',
        'logo_hint' => 'Optionales Logo für die öffentliche Kanalliste. PNG, JPEG oder WebP, höchstens 512 KB. Ohne Logo wird ein neutrales Symbol angezeigt.',
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
