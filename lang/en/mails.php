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
    'channel_reception_paused' => [
        'subject'        => 'Video reception paused – :channel',
        'headline'       => 'Your video reception has been paused',
        'greeting'       => 'Hello,',
        'body'           => 'The weekly video reception for channel <strong>:channel</strong> has been paused by an administrator. If you did not request this, you can reactivate reception using the button below.',
        'reactivate_cta' => 'Reactivate reception',
        'signature'      => 'Best regards,<br>Your :app team',
    ],
    'user_inactivity_reminder' => [
        'subject' => 'We haven\'t seen you in a while',
        'headline' => 'We missed you',
        'body_without_login' => 'we haven\'t recorded a login from you at :app in a while. Come back and check what\'s new!',
        'greeting' => 'Hi :name,',
        'body' => 'you haven\'t logged in to :app since :date. Come back and check what\'s new!',
        'cta' => 'Log in now',
        'opt_out_hint' => 'You can turn off this reminder any time in your profile under "Notifications per Mail".',
        'signature' => 'Best regards<br>Your :app team',
    ],
    'offer_download_ready' => [
        'subject' => 'Your download is ready',
        'headline' => 'Your download is ready',
        'greeting' => 'Hi :name,',
        'ready' => '{1} One video from “:channel” is ready for you as a ZIP.|[2,*] :count videos from “:channel” are ready for you as a ZIP.',
        'ready_without_channel' => '{1} One video is ready for you as a ZIP.|[2,*] :count videos are ready for you as a ZIP.',
        'skipped' => '{1} One video could not be packed and is missing from the ZIP.|[2,*] :count videos could not be packed and are missing from the ZIP.',
        'button' => 'Download ZIP',
        'validity' => 'The download stays ready for one day. After that you can simply prepare it again under “My Offers”.',
        'opt_out_hint' => 'You can turn off this email at any time in your profile under “Notifications per Mail”.',
        'signature' => 'Best regards<br>Your :app team',
    ],
];
