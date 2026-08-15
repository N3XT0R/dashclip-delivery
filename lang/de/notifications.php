<?php

declare(strict_types=1);

use App\Notifications\ChannelAccessApprovedNotification;
use App\Notifications\UserInactivityReminderNotification;
use App\Notifications\UserUploadDuplicatedNotification;
use App\Notifications\UserUploadProceedNotification;

return [
    'mail' => [
        'title' => 'Benachrichtigungen per E-Mail',
        'types' => [
            UserUploadDuplicatedNotification::class => 'Benachrichtigen bei doppelten Uploads',
            UserUploadProceedNotification::class => 'Benachrichtigen nach erfolgreicher Verarbeitung',
            ChannelAccessApprovedNotification::class => 'Benachrichtigen wenn der Kanalzugriff genehmigt wurde',
            UserInactivityReminderNotification::class => 'Erinnern, wenn ich länger nicht eingeloggt war',
        ],

    ],
    'channel_access_approved' => [
        'title' => 'Kanalzugriff genehmigt',
        'body' => 'Ihr Zugriff auf den Kanal ":channelName" wurde genehmigt.',
    ],
    'user_upload_duplicated' => [
        'body' => 'Die Datei ":filename" wurde als Duplikat erkannt und wurde gelöscht.',
    ],
    'user_upload_proceed' => [
        'body' => 'Ihre Datei ":filename" wurde erfolgreich verarbeitet.',
    ],
];
