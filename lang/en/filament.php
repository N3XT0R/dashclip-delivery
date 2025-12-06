<?php

declare(strict_types=1);

return [
    'channel_application' => [
        'title' => 'Request Access to Channel Videos',
        'navigation_label' => 'Request Access',
        'navigation_group' => __('nav.channel_owner'),
        'form' => [
            'about_title' => 'Benefits of Optional Registration',
            'about_intro' => 'By registering as a channel operator, you gain extra security and control for your video offers – your usual workflow remains unchanged.',
            'about_benefit_security' => 'Only registered and logged-in channel operators can use exclusive offer links, keeping your content even safer from unauthorized access.',
            'about_benefit_control' => 'Your user account is directly linked to your channel. You can securely manage all offers and keep track of every action.',
            'about_benefit_remain' => 'Registration is optional and your familiar access via email link remains fully functional.',
            'about_footer' => 'Access is granted after a brief review. You will then have access to all current and future offers for your channel.',
            'about_benefit_security_title' => 'More Security',
            'about_benefit_control_title' => 'Full Control',
            'about_benefit_remain_title' => 'Voluntary & Flexible',
            'note_label' => 'Reason',
            'note_placeholder' => 'Please briefly state why you need access to the videos from this channel.',
            'submit' => 'Submit Application',
        ],
        'messages' => [
            'application_submitted' => 'Application submitted!',
        ],
    ],
];