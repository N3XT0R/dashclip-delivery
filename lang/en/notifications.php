<?php

declare(strict_types=1);

use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;

return [
    'mail' => [
        'title' => 'Notifications per Mail',
        'types' => [
            UserUploadDuplicatedNotification::class => 'Notify on duplicated entries',
            UserUploadProceedNotification::class => 'Notify when user upload is processed',
        ],
    ],
];
