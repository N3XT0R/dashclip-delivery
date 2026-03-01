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
    'channel_access_approved' => [
        'title' => 'Channel Access Approved',
        'body' => 'Your access to the channel ":channelName" has been approved.',
    ],

    'user_upload_duplicated' => [
        'body' => 'The file ":filename" was detected as a duplicate and was deleted.',
    ],
];
