<?php

declare(strict_types=1);

return [
    'channel_access_request' => [
        'subject' => 'Approve channel access',

        'headline' => 'Channel access request',

        'greeting' => 'Hello :name,',

        'intro' =>
            'An access request has been submitted for the following channel:',

        'instruction' =>
            'If you would like to approve this access, please confirm using the button below.',

        'approve' => 'Approve access',

        'outro' =>
            'Once approved, the requesting person will be able to access the channel according to the granted permissions.',

        'revoke_hint' =>
            'Access can be revoked at any time by authorized persons.',

        'signature' => 'Best regards<br>Your :app team',
    ],
    'channel_access_approved' => [
        'subject' => 'Channel access approved',

        'headline' => 'Access approved',

        'greeting' => 'Hello :name,',

        'intro' =>
            'Your access request for the following channel has been approved:',

        'access_notice' =>
            'You can now access the channel according to the granted permissions.',

        'signature' => 'Best regards<br>Your :app team',
    ],
];
