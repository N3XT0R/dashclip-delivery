<?php

declare(strict_types=1);

return [
    'original' => [
        'label' => 'Original version',
        'action' => 'Hand out the original',
        'confirm' => 'From now on channels get the untouched version with readable number plates. '
            . 'The previews are made again. Channels that already downloaded the video keep what '
            . 'they have and are told about the change by mail.',
    ],
    'blurred' => [
        'label' => 'Blurred version',
        'action' => 'Hand out the blurred version',
        'confirm' => 'From now on channels get the version with blurred number plates. The previews '
            . 'are made again. Channels that already downloaded the video keep what they have and '
            . 'are told about the change by mail.',
    ],
    'switched' => 'Version changed',
    'switched_hint' => 'The submitter chose on :date which version is handed out. '
        . 'A file downloaded earlier may differ from it.',
    'notification' => [
        'title' => 'Version changed',
        'body' => 'From now on :video is handed out as the :version.',
    ],
];
