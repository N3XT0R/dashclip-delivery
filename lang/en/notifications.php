<?php

declare(strict_types=1);

use App\Notifications\ChannelAccessApprovedNotification;
use App\Notifications\UserInactivityReminderNotification;
use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;

return [
    'mail' => [
        'title' => 'Notifications per Mail',
        'types' => [
            UserUploadDuplicatedNotification::class => 'Notify on duplicated entries',
            UserUploadProceedNotification::class => 'Notify when user upload is processed',
            ChannelAccessApprovedNotification::class => 'Notify when channel access is approved',
            UserInactivityReminderNotification::class => 'Remind me when I haven\'t logged in for a while',
        ],
    ],
    'channel_access_approved' => [
        'title' => 'Channel Access Approved',
        'body' => 'Your access to the channel ":channelName" has been approved.',
    ],
    'user_upload_duplicated' => [
        'body' => 'The file ":filename" was detected as a duplicate and was deleted.',
    ],
    'user_upload_proceed' => [
        'body' => 'Your file ":filename" has been successfully processed.',
    ],
];
