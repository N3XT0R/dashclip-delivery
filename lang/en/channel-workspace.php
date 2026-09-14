<?php

declare(strict_types=1);
return [
    'title' => 'Channel Settings',
    'channel_resource' => [
        'creator_name' => 'Creator Name',
        'show_on_homepage' => 'Show on the homepage',
        'show_on_homepage_hint' => 'While this is active, your channel appears in the public channel list on the homepage.',
        'logo' => 'Logo',
        'logo_hint' => 'Optional logo for the public channel list. PNG, JPEG or WebP, at most 512 KB. Without a logo a neutral symbol is shown.',
        'relation_manager' => [
            'users' => [
                'title' => 'assigned Users',
                'columns' => [
                    'channel' => 'Channel',
                    'email' => 'Email',
                    'verified' => 'Verified',
                ],
            ],
        ],
    ],
];
