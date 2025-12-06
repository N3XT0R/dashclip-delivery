<?php

declare(strict_types=1);

return [
    'channel_application' => [
        'title' => 'Zugang zu Kanalvideos beantragen',
        'navigation_label' => 'Zugang beantragen',
        'navigation_group' => __('nav.channel_owner'),
        'form' => [
            'note_label' => 'Begründung',
            'note_placeholder' => 'Geben Sie hier einen kurzen Grund an, warum Sie Zugang zu den Videos dieses Kanals benötigen.',
            'submit' => 'Anfrage absenden',
        ],
        'messages' => [
            'application_submitted' => 'Anfrage eingereicht!',
        ],
    ],
];