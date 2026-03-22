<?php

declare(strict_types=1);

return [
    'common' => [
        'expires_at' => 'This link will expire on :date.',
        'unknown_user' => 'Unknown user',
        'channel' => 'Channel:',
    ],
    'channel_access_request' => [
        'subject' => 'Approve channel access',

        'headline' => 'Channel access request',

        'greeting' => 'Hello :name,',

        'intro' =>
            'An access request has been submitted for the following channel:',
        'requested_by' => 'Access request submitted by:',

        'instruction' =>
            'If you would like to approve this access, please confirm using the button below.',

        'approve' => 'Approve access',

        'outro' =>
            'Once approved, the requesting person will be able to access the channel according to the granted permissions.',

        'revoke_hint' =>
            'Access can be revoked at any time by authorized persons.',

        'signature' => 'Best regards<br>Your :app team',
        'note_label' => 'Message from the applicant:',
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
    'channel_welcome_email' => [
        'subject' => 'Welcome to the weekly video delivery',
        'headline' => 'Please confirm your subscription to the weekly video delivery',
        'greeting' => 'Hi :name,',
        'channel_registered' => 'Your channel has been registered with <strong>:app_name</strong> so you can receive new videos regularly right when they are published. Before the delivery starts, please confirm your participation.',
        'weekly_opt_in' => 'If you agree to receive the weekly delivery, simply click here:',
        'approve' => 'Confirm subscription',
        'after_confirmation' => 'After confirmation, you will automatically receive new videos at the usual intervals. If you no longer wish to receive them, just send a short email to <a href="mailto::email">:email</a>.',
        'signature' => 'Best regards,<br>Your :app_name Team',
    ],
];
