<?php

declare(strict_types=1);

return [
    'channel_activation' => [
        'title' => 'Participation confirmed',
        'subtitle' => 'Channel confirmation',
        'headline' => 'Participation successfully confirmed',

        'thanks' => 'Thank you, :name!',

        'description' =>
            'Access to the channel has been successfully granted.',

        'availability_notice' =>
            'From now on, the channel is available to authorized persons according to the granted permissions.',

        'revoke_notice' =>
            'Access can be revoked at any time via the user account or by authorized persons.',

        'back' => 'Back to home',
    ],
    'channel_access' => [
        'title' => 'Access confirmed',
        'subtitle' => 'Channel access',
        'headline' => 'Access successfully confirmed',

        'thanks' => 'Thank you!',

        'description' =>
            'The access request for the channel :channel has been successfully approved.',

        'access_granted' =>
            'The approved person can now access the channel according to the granted permissions.',

        'revoke_notice' =>
            'Access can be revoked at any time by the channel owner or authorized team members.',

        'back' => 'Back to home',
    ],
];
