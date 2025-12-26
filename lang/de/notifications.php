<?php

declare(strict_types=1);

use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;

return [
    'mail' => [
        'title' => 'Benachrichtigungen per E-Mail',
        'types' => [
            UserUploadDuplicatedNotification::class => 'Benachrichtigen bei doppelten Uploads',
            UserUploadProceedNotification::class => 'Benachrichtigen nach erfolgreicher Verarbeitung',
        ],

    ],
    'channel_access_approved' => [
        'title' => 'Kanalzugriff genehmigt',
        'body' => 'Ihr Zugriff auf den Kanal ":channelName" wurde genehmigt.',
    ],
];
