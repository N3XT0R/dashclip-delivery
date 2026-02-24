<?php

declare(strict_types=1);

return [
    'title' => 'My Offers',
    'navigation_label' => 'My Offers',
    'navigation_group' => 'Media',
    'notifications' => [
        'note_updated' => [
            'title' => 'Comment saved',
        ],
    ],
    'tabs' => [
        'available' => 'Available',
        'downloaded' => 'Downloaded',
        'expired' => 'Expired',
        'returned' => 'Rejected',
    ],

    'stats' => [
        'available' => [
            'label' => 'Available Offers',
            'downloaded_from_available' => 'Already downloaded',
            'avg_validity_days' => 'Ø validity (days)',
        ],
        'downloaded' => [
            'label' => 'Downloaded',
            'total' => 'Total',
            'avg_download_days_ago' => 'Ø days ago',
        ],
        'expired' => [
            'label' => 'Expired',
            'total' => 'Total',
            'downloaded_count' => 'Downloaded',
            'missed_count' => 'Missed',
        ],
    ],

    'table' => [
        'columns' => [
            'video_title' => 'Video',
            'uploader' => 'Uploader',
            'valid_until' => 'Valid until',
            'remaining_days' => ':days days remaining',
            'remaining_hours' => ':hours hours remaining',
            'status' => 'Status',
            'offered_at' => 'Offered on',
            'downloaded_at' => 'Downloaded on',
            'expired_at' => 'Expired on',
            'returned_at' => 'Rejected on',
            'was_downloaded' => 'Downloaded?',
            'return_reason' => 'Reason',
        ],
        'status_badges' => [
            'available' => 'Available',
            'downloaded' => 'Downloaded',
            'yes' => 'Yes',
            'no' => 'No',
        ],
        'actions' => [
            'view_details' => 'Details',
            'download' => 'Download',
            'download_again' => 'Download again',
            'return_offer' => 'Reject',
            'save_notes' => 'Save comment',
        ],
        'bulk_actions' => [
            'download_all' => 'Download all',
            'download_selected' => 'Download selected',
            'return_selected' => 'Reject selected',
            'return_selected_notification' => 'Selected offers have been rejected.',
        ],
        'empty_state' => [
            'heading' => 'No offers available',
            'description' => 'As soon as videos are available for you, they will appear here.',
        ],
    ],

    'modal' => [
        'title' => 'Video Details',
        'metadata' => [
            'heading' => 'Metadata',
            'file_size' => 'File size',
            'duration' => 'Duration',
            'filename' => 'Filename',
        ],
        'note' => [
            'heading' => 'Message to the submitter',
            'label' => 'Comment from the channel owner',
            'placeholder' => 'Enter your comment here...',
            'helper_text' => 'This comment will be saved with the offer and shown to the submitter.',
        ],
        'clips' => [
            'heading' => 'Clip Information',
            'role' => 'Role',
            'timing' => 'Timing',
            'submitter' => 'Submitter',
            'notes' => 'Notes',
            'no_clips' => 'No clips available',
        ],
        'preview' => [
            'heading' => 'Preview',
            'not_available' => 'No preview available',
        ],
    ],
    'messages' => [
        'no_videos_downloaded' => 'You have not downloaded any videos yet.',
        'no_expired_offers' => 'You have no expired offers.',
        'no_returned_offers' => 'You have no rejected offers.',
    ],
];
