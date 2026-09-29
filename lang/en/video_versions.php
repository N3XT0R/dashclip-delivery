<?php

declare(strict_types=1);

return [
    'blur_now' => [
        'action' => 'Blur the number plates',
        'confirm' => 'We look for the number plates in this video and blur them. Depending on the '
            . 'length this takes a few minutes and runs in the background. Your original is kept, '
            . 'and afterwards you can switch between the two versions at any time.',
        'queued' => 'The video is being worked on in the background. You will hear from us once it is done.',
    ],
    'blur_done' => [
        'title' => 'Number plates blurred',
        'body' => 'The blurred version of :video is handed out from now on. The original is kept.',
    ],
    'blur_failed' => [
        'title' => 'Blurring failed',
        'body' => 'It did not work for :video. The version that is handed out is unchanged.',
    ],
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
