<?php

declare(strict_types=1);

return [
    'channel_application' => [
        'title' => 'Request Access to Channel Videos',
        'navigation_label' => 'Request Access',
        'navigation_group' => __('nav.channel_owner'),
        'form' => [
            'about_title' => 'Benefits of Optional Registration',
            'about_intro' => 'By registering for free as a channel operator, you gain extra security and control for your channel offers—without changing your current workflow.',
            'about_benefit_security_title' => 'More Security',
            'about_benefit_security' => 'Only registered and logged-in channel operators can use exclusive offer links, providing even better protection against unauthorized access.',
            'about_benefit_control_title' => 'Full Control',
            'about_benefit_control' => 'Your user account is directly linked to your channel. You can securely manage all offers and keep track of every action.',
            'about_benefit_portal_title' => 'Everything at a Glance',
            'about_benefit_portal' => 'All videos assigned to you are not only sent by email offers, but are also clearly and conveniently available at any time in your personal portal. There you can manage, download, and check the status of all your clips.',
            'about_benefit_remain_title' => 'Voluntary & Flexible',
            'about_benefit_remain' => 'Registration is voluntary, and your previous access via email link remains fully available.',
            'about_footer' => 'Access is granted after a short review. You will then have access to all current and future offers for your channel.',
            'request_other_channel' => 'My channel is not in the list',
            'channel_label' => 'Select an existing channel',
            'new_channel_section_label' => 'Details for new channel',
            'new_channel_name_label' => 'Channel name',
            'new_channel_name_placeholder' => 'Please enter the full name of your requested channel',
            'new_channel_creator_name_label' => 'Operator name',
            'new_channel_creator_name_placeholder' => 'Name of the responsible person or organization',
            'new_channel_email_label' => 'Contact email',
            'new_channel_email_placeholder' => 'Enter the email address for the channel',
            'new_channel_youtube_name_label' => 'YouTube channel (optional)',
            'new_channel_youtube_name_placeholder' => 'YouTube channel name (optional)',
            'note_label' => 'Reason',
            'note_placeholder' => 'Please briefly explain why you need access to the videos in this channel.',
            'submit' => 'Submit application',
            'status_title' => 'Application already submitted for :channel',
            'status_message' => 'You have already submitted an application for this channel. Status: :status',
            'status_note' => 'Please wait until your application is processed or contact support.',
            'submitted_at' => 'Submitted on:',
            'choose_channel' => 'Choose a channel',
        ],
        'status' => [
            'pending' => 'Pending',
            'approved' => 'Approved',
            'rejected' => 'Rejected',
        ],
        'messages' => [
            'success' => [
                'application_submitted' => 'Application submitted!',
            ],
            'error' => [
                'already_applied' => 'You have already submitted an application for this channel.',
                'no_channels' => 'No channels available for application.',
            ],
        ],
    ],
    'admin_channel_application' => [
        'navigation_label' => 'Channel Access Applications',
        'navigation_group' => __('nav.channels'),
        'table' => [
            'columns' => [
                'applicant' => 'Applicant',
                'channel' => 'Channel',
                'status' => 'Status',
                'submitted_at' => 'Submitted at',
                'updated_at' => 'Updated at',
            ],
        ],
    ],
];