<?php

declare(strict_types=1);

return [
    'title' => 'Settings',
    'description' => 'Applies to every video this team uploads.',
    'censor_license_plates' => [
        'label' => 'Blur number plates',
        'description' => 'Number plates that are found are blurred before the video is offered. '
            . 'The original is kept. The detection is a help, not a guarantee, and a plate can '
            . 'slip through.',
    ],
    'censor_faces' => [
        'label' => 'Blur faces',
        'description' => 'Faces that are found are blurred before the video is offered.',
    ],
];
