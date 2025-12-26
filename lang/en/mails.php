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
];
